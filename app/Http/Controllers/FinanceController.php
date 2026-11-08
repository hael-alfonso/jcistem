<?php
namespace App\Http\Controllers;

use App\Models\{Project, BudgetAllocation, LedgerEntry, MemberDue, ProjectFundRelease, User};
use App\Services\ChapterService;
use App\Support\{ChapterCharts, ChapterFunds, Permissions, ProjectBudget, ProjectFinanceGuard};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class FinanceController extends Controller
{
    private function treasurer(Request $request): void { abort_unless(Permissions::allows($request->user(), Permissions::FINANCE_MANAGE), 403); }

    public function index(Request $request)
    {
        $projects = ChapterService::visibleProjects($request->user())
            ->whereIn('status', Project::APPROVED)->with('chair')->latest()->get();
        $charts = ChapterCharts::finances($projects);
        $chapterFunds = ChapterFunds::postedBalance();
        return view('chapter.finance', compact('charts', 'chapterFunds'));
    }

    public function budget(Request $request, Project $project)
    {
        $this->treasurer($request);
        $data = $request->validate(['budget_item_id' => 'required|uuid', 'amount' => 'required|numeric|decimal:0,2|min:0|max:999999999.99', 'approved_by' => 'required|string|max:200', 'approved_on' => 'required|date|before_or_equal:today', 'remarks' => 'required|string|max:5000']);
        DB::transaction(function () use ($project, $data, $request) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_if($project->chair_id === $request->user()->id, 403, 'An independent Treasurer must record this project allocation.');
            abort_unless(in_array($project->status, ['Plan Locked', 'Ongoing'], true)
                || ($project->status === 'Approved' && $project->proposal_reviewed_at),
                422, 'The full proposal must receive formal approval before new budget allocations are recorded.');
            $item = collect($project->budget_items ?? [])->firstWhere('id', $data['budget_item_id']);
            if (!$item) throw ValidationException::withMessages(['budget_item_id' => 'Choose a specific expense from the approved project budget.']);
            if (\App\Support\Money::cents($data['amount']) > \App\Support\Money::cents($item['amount'])) throw ValidationException::withMessages(['amount' => 'The allocation cannot exceed this proposed expense amount.']);
            $allocation = BudgetAllocation::firstOrNew(['project_id' => $project->id, 'budget_item_id' => $data['budget_item_id']]);
            $before = $allocation->toArray();
            $lineSpent = \App\Support\Money::cents($project->transactions()->where('budget_item_id', $data['budget_item_id'])->where('status', 'Posted')->where('direction', 'debit')->sum('amount'));
            $pending = \App\Support\Money::cents($project->expenseClaims()->where('budget_item_id', $data['budget_item_id'])->where('status', 'Pending')->sum('amount'))
                + \App\Support\Money::cents($project->fundReleases()->where('budget_item_id', $data['budget_item_id'])->where('status', 'Pending')->sum('amount'));
            if (\App\Support\Money::cents($data['amount']) < $lineSpent + $pending) throw ValidationException::withMessages(['amount' => 'Allocation cannot fall below posted spending and pending claims for this expense.']);
            $total = \App\Support\Money::cents($project->allocations()->sum('amount')) - \App\Support\Money::cents($allocation->amount) + \App\Support\Money::cents($data['amount']);
            if ($project->decisions()->where('decision', 'approved')->exists() && $total > \App\Support\Money::cents($project->proposed_budget)) throw ValidationException::withMessages(['amount' => 'Allocations cannot exceed the BOD-approved budget.']);
            if ($total < \App\Support\Money::cents($project->transactions()->where('status', 'Posted')->where('direction', 'debit')->sum('amount'))) throw ValidationException::withMessages(['amount' => 'Allocation cannot be reduced below posted expenses.']);
            $allocation->fill($data + ['category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense', 'budget_item_description' => $item['description'], 'recorded_by' => $request->user()->id])->save();
            $project->touch();
            ChapterService::audit('budget_allocation_saved', $allocation, $before);
            ChapterService::notify([$project->chair_id], 'Project budget allocation updated', $project->title.' · '.$item['description'].' · PHP '.number_format((float) $allocation->amount, 2), route('projects.show', $project, false).'#budget');
        });
        return back()->with('success', 'Budget allocation saved.');
    }

    public function liquidationReport(Request $request, Project $project)
    {
        $this->treasurer($request);
        $project->load(['owner', 'chair', 'allocations']);
        $entries = $project->transactions()->where('status', 'Posted')->orderBy('transaction_date')->orderBy('id')->get();
        $expenses = $entries->where('direction', 'debit');
        $income = $entries->where('direction', 'credit');
        $spentByCategory = $expenses->groupBy('category')->map(fn ($rows) => $rows->sum('amount'));

        return view('chapter.liquidation-report', compact('project', 'entries', 'expenses', 'income', 'spentByCategory'));
    }

    public function ledger(Request $request)
    {
        $this->treasurer($request);
        $request->validate(['project' => 'nullable|integer|exists:projects,id', 'q' => 'nullable|string|max:200', 'status' => 'nullable|in:Posted,Void', 'direction' => 'nullable|in:credit,debit', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : ''), 'filter' => 'nullable|in:liquidation']);
        $selectedProject = $request->filled('project') ? Project::withTrashed()->findOrFail($request->integer('project')) : null;
        $entries = $this->filteredLedger($request)->with(['project', 'member'])->latest('transaction_date')->latest('id')->paginate(30)->withQueryString();
        $projects = Project::whereIn('status', ['Plan Locked', 'Ongoing'])->orderBy('title')->get();
        $filterProjects = Project::withTrashed()->whereHas('transactions')->orderBy('title')->get(['id', 'title']);
        $members = User::where('status', 'active')->orderBy('name')->get();
        return view('chapter.ledger', compact('entries', 'projects', 'filterProjects', 'members', 'selectedProject'));
    }

    public function store(Request $request)
    {
        $this->treasurer($request);
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:ledger_entries,reference',
            'project_id' => 'nullable|integer|exists:projects,id', 'member_id' => 'nullable|integer|exists:users,id', 'budget_item_id' => 'nullable|uuid',
            'transaction_date' => 'required|date', 'type' => ['required', Rule::in(['Income', 'Expense', 'Disbursement', 'Adjustment credit', 'Adjustment debit'])],
            'category' => 'required|string|max:100', 'account' => 'required|string|max:150', 'counterparty' => 'required|string|max:200',
            'payment_method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])],
            'description' => 'required|string|max:5000', 'amount' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'remarks' => 'nullable|string|max:5000', 'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $data['direction'] = in_array($data['type'], ['Expense', 'Disbursement', 'Adjustment debit']) ? 'debit' : 'credit';
        $data['liquidation_status'] = $data['direction'] === 'debit' ? 'Pending' : 'Not required';
        $data['recorded_by'] = $request->user()->id;
        unset($data['receipt']);
        $path = null;
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('financial-documents', 'local');
            $data['document_path'] = $path; $data['document_name'] = $request->file('receipt')->getClientOriginalName();
        }
        try {
            DB::transaction(function () use ($data, $request) {
                if (!empty($data['project_id'])) {
                    $project = Project::lockForUpdate()->findOrFail($data['project_id']);
                    abort_unless(in_array($project->status, ['Plan Locked', 'Ongoing']), 422, 'Transactions require a formally approved, active project.');
                    if ($data['direction'] === 'debit') {
                        abort_if($project->chair_id === $request->user()->id, 403, 'A Chair cannot verify their own project expense.');
                        $item = ProjectFinanceGuard::expenseLine($project, $data['budget_item_id'] ?? '', $data['amount']);
                        $data['category'] = ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense';
                    }
                    $remaining = \App\Support\Money::cents($project->allocations()->sum('amount')) - \App\Support\Money::cents($project->transactions()->where('status', 'Posted')->where('direction', 'debit')->sum('amount'));
                    if ($data['direction'] === 'debit' && \App\Support\Money::cents($data['amount']) > $remaining) throw ValidationException::withMessages(['amount' => 'This expense exceeds the available allocation. Increase the authorized allocation first.']);
                }
                $entry = LedgerEntry::create($data + ['status' => 'Posted']);
                ChapterService::audit('transaction_posted', $entry);
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Transaction posted to the official ledger.');
    }

    public function update(Request $request, LedgerEntry $entry)
    {
        $this->treasurer($request);
        $data = $request->validate(['action' => ['required', Rule::in(['void', 'liquidate'])], 'remarks' => 'required|string|max:5000', 'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $path = $request->hasFile('receipt') ? $request->file('receipt')->store('financial-documents', 'local') : null;
        try {
            DB::transaction(function () use ($entry, $data, $path, $request) {
                if ($entry->project_id) {
                    $project = Project::lockForUpdate()->findOrFail($entry->project_id);
                    abort_if(in_array($project->status, ['Archived', 'Completed', 'Cancelled']), 403, 'Closed project finances are locked.');
                    if ($data['action'] === 'liquidate') abort_if($project->chair_id === $request->user()->id, 403, 'An independent Treasurer must verify liquidation.');
                    abort_if($project->status === 'Completion Review' && $data['action'] === 'void', 403, 'Financial corrections require return to implementation.');
                    abort_if($project->reports()->where('type', 'Liquidation')->latest('id')->first()?->status === 'Reviewed', 403, 'Approved liquidation evidence is locked.');
                }
                if ($entry->member_due_id) MemberDue::lockForUpdate()->findOrFail($entry->member_due_id);
                $entry = LedgerEntry::lockForUpdate()->findOrFail($entry->id);
                abort_unless($entry->status === 'Posted', 422, 'Only posted transactions may be changed.');
                $before = $entry->toArray();
                if ($data['action'] === 'void') {
                    $entry->status = 'Void'; $entry->void_reason = $data['remarks'];
                } else {
                    abort_unless($entry->direction === 'debit', 422);
                    if (!$path && !$entry->document_path) throw ValidationException::withMessages(['receipt' => 'A receipt or supporting document is required for liquidation.']);
                    $entry->liquidation_status = 'Liquidated'; $entry->remarks = $data['remarks'];
                }
                if ($path) { $entry->document_path = $path; $entry->document_name = $request->file('receipt')->getClientOriginalName(); }
                $entry->save();
                if ($data['action'] === 'void') {
                    $claim = \App\Models\ExpenseClaim::where('ledger_entry_id', $entry->id)->first();
                    if ($claim) {
                        $claimBefore = $claim->toArray();
                        $claim->status = 'Void';
                        $claim->save();
                        ChapterService::audit('expense_claim_voided', $claim, $claimBefore, $data['remarks']);
                        ChapterService::notify([$claim->submitted_by], 'Expense claim voided', $entry->reference.' was voided: '.$data['remarks'], route('expense-claims.index', [], false), true);
                    }
                    $release = ProjectFundRelease::where('ledger_entry_id', $entry->id)->lockForUpdate()->first();
                    if ($release) {
                        $releaseBefore = $release->toArray();
                        $release->status = 'Voided';
                        $release->cancel_note = $data['remarks'];
                        $release->cancelled_at = now();
                        $release->save();
                        ChapterService::audit('fund_release_voided', $release, $releaseBefore, $data['remarks']);
                        ChapterService::notify([$release->chair_id], 'Project fund release voided', $entry->reference.' was voided: '.$data['remarks'], route('projects.show', $release->project_id, false).'#budget', true);
                    }
                }
                ChapterService::audit('transaction_'.$data['action'], $entry, $before, $data['remarks']);
                if ($entry->member_due_id) ChapterService::notify([$entry->member_id], 'Dues payment updated', $entry->reference.' was '.$entry->status, route('dues', [], false));
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Transaction updated with audit history preserved.');
    }

    public function receipt(Request $request, LedgerEntry $entry)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::FINANCE_MANAGE) || ($entry->project_id && $entry->project->isChair($request->user())) || ($entry->member_due_id && $entry->member_id === $request->user()->id), 403);
        abort_unless($entry->document_path && Storage::disk('local')->exists($entry->document_path), 404);
        return Storage::disk('local')->download($entry->document_path, $entry->document_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function dues(Request $request)
    {
        $all = $request->routeIs('dues.manage');
        if ($all) $this->treasurer($request);
        $request->validate(['q' => 'nullable|string|max:200', 'member' => 'nullable|integer|exists:users,id', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')]);
        $dues = MemberDue::with(['member', 'payments'])->when(!$all, fn ($q) => $q->where('member_id', $request->user()->id))
            ->when($all && $request->filled('member'), fn ($q) => $q->where('member_id', $request->integer('member')))
            ->when($all && $request->filled('q'), fn ($q) => $q->whereHas('member', fn ($member) => $member->where('name', 'like', '%'.$request->string('q').'%')->orWhere('member_no', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('due_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('due_date', '<=', $request->query('to')))
            ->latest('period')->paginate(30)->withQueryString();
        $members = $all ? User::where('status', 'active')->orderBy('name')->get() : collect();
        return view('chapter.dues', compact('dues', 'members', 'all'));
    }

    public function openDues(Request $request)
    {
        $this->treasurer($request);
        $data = $request->validate(['period' => 'required|date_format:Y-m', 'due_date' => 'required|date', 'amount' => 'required|numeric|decimal:0,2|min:0|max:9999999.99', 'member_id' => 'nullable|integer|exists:users,id', 'remarks' => 'required|string|max:5000']);
        DB::transaction(function () use ($data, $request) {
            $members = User::where('status', 'active')->when(!empty($data['member_id']), fn ($q) => $q->whereKey($data['member_id']))->lockForUpdate()->get();
            foreach ($members as $member) {
                $due = MemberDue::firstOrNew(['member_id' => $member->id, 'period' => $data['period']]);
                $before = $due->toArray();
                if ($due->exists && \App\Support\Money::cents($data['amount']) < \App\Support\Money::cents($due->payments()->where('status', 'Posted')->sum('amount'))) throw ValidationException::withMessages(['amount' => 'The dues amount cannot be lower than payments already posted.']);
                $due->fill(['due_date' => $data['due_date'], 'amount' => $data['amount'], 'remarks' => $data['remarks'], 'recorded_by' => $request->user()->id])->save();
                ChapterService::audit('dues_assessed_or_adjusted', $due, $before, $data['remarks']);
                ChapterService::notify([$member->id], 'Member dues: '.$due->period, 'Amount due: PHP '.number_format($due->balance, 2).'. Due '.$due->due_date->format('M d, Y').'.', route('dues', [], false));
            }
        });
        return back()->with('success', 'Dues period saved. Members have been notified.');
    }

    public function payDues(Request $request, MemberDue $due)
    {
        $this->treasurer($request);
        $data = $request->validate(['reference' => 'required|string|max:100|unique:ledger_entries,reference', 'amount' => 'required|numeric|decimal:0,2|min:0.01|max:9999999.99', 'transaction_date' => 'required|date', 'payment_method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])], 'remarks' => 'nullable|string|max:5000', 'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        unset($data['receipt']);
        $path = $request->hasFile('receipt') ? $request->file('receipt')->store('financial-documents', 'local') : null;
        if ($path) { $data['document_path'] = $path; $data['document_name'] = $request->file('receipt')->getClientOriginalName(); }
        try {
            DB::transaction(function () use ($due, $data, $request) {
                $due = MemberDue::lockForUpdate()->findOrFail($due->id);
                if (\App\Support\Money::cents($data['amount']) > \App\Support\Money::cents($due->amount) - \App\Support\Money::cents($due->payments()->where('status', 'Posted')->sum('amount'))) throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding dues balance.']);
                $entry = LedgerEntry::create($data + ['member_id' => $due->member_id, 'member_due_id' => $due->id, 'recorded_by' => $request->user()->id, 'type' => 'Dues Collection', 'direction' => 'credit', 'category' => 'Member dues', 'account' => 'Chapter fund', 'counterparty' => $due->member->name, 'description' => 'Member dues for '.$due->period, 'status' => 'Posted']);
                ChapterService::audit('dues_payment_posted', $entry);
                ChapterService::notify([$due->member_id], 'Dues payment received', 'PHP '.number_format($entry->amount, 2).' recorded for '.$due->period.'. Reference '.$entry->reference.'.', route('dues', [], false));
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Payment recorded in dues and the Treasurer ledger.');
    }

    public function export(Request $request)
    {
        $this->treasurer($request);
        $request->validate(['project' => 'nullable|integer|exists:projects,id', 'q' => 'nullable|string|max:200', 'status' => 'nullable|in:Posted,Void', 'direction' => 'nullable|in:credit,debit', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : ''), 'filter' => 'nullable|in:liquidation']);
        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Type', 'Project', 'Description', 'Credit', 'Debit', 'Status']);
            foreach ($this->filteredLedger($request)->with('project')->orderBy('transaction_date')->orderBy('id')->cursor() as $entry) {
                $safe = fn ($s) => preg_match('/^[=+@\\-]/', (string) $s) ? "'".$s : $s;
                fputcsv($out, array_map($safe, [$entry->reference, $entry->transaction_date?->format('Y-m-d'), $entry->type, $entry->project?->reference, $entry->description, $entry->direction === 'credit' ? $entry->amount : '', $entry->direction === 'debit' ? $entry->amount : '', $entry->status]));
            }
            fclose($out);
        }, 'jci-carmona-ledger.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredLedger(Request $request)
    {
        return LedgerEntry::query()
            ->when($request->filled('project'), fn ($q) => $q->where('project_id', $request->integer('project')))
            ->when($request->query('filter') === 'liquidation', fn ($q) => $q->where('direction', 'debit')->where('status', 'Posted'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->query('direction')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->query('to')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($match) => $match->where('reference', 'like', '%'.$request->string('q').'%')
                ->orWhere('description', 'like', '%'.$request->string('q').'%')
                ->orWhere('counterparty', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('project', fn ($project) => $project->where('title', 'like', '%'.$request->string('q').'%'))
                ->orWhereHas('member', fn ($member) => $member->where('name', 'like', '%'.$request->string('q').'%'))));
    }
}

