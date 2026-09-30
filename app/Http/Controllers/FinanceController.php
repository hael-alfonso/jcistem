<?php
namespace App\Http\Controllers;

use App\Models\{Project, BudgetAllocation, LedgerEntry, MemberDue, User};
use App\Services\ChapterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class FinanceController extends Controller
{
    private function treasurer(Request $request): void { abort_unless($request->user()->role === 'treasurer', 403); }

    public function index(Request $request)
    {
        $projects = Project::with('chair')->whereIn('status', Project::APPROVED)->latest()->get();
        return view('chapter.finance', compact('projects'));
    }

    public function budget(Request $request, Project $project)
    {
        $this->treasurer($request);
        $data = $request->validate(['category' => 'required|string|max:100', 'amount' => 'required|numeric|min:0|max:999999999.99', 'approved_by' => 'required|string|max:200', 'approved_on' => 'required|date', 'remarks' => 'required|string|max:5000']);
        DB::transaction(function () use ($project, $data, $request) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless(in_array($project->status, ['Approved', 'Ongoing']), 422, 'Budget allocations require an approved active project.');
            $allocation = BudgetAllocation::firstOrNew(['project_id' => $project->id, 'category' => $data['category']]);
            $before = $allocation->toArray();
            $total = round($project->allocated - (float) $allocation->amount + (float) $data['amount'], 2);
            if ($total < $project->spent) throw ValidationException::withMessages(['amount' => 'Allocation cannot be reduced below posted expenses.']);
            $allocation->fill($data + ['recorded_by' => $request->user()->id])->save();
            ChapterService::audit('budget_allocation_saved', $allocation, $before);
        });
        return back()->with('success', 'Budget allocation saved.');
    }

    public function ledger(Request $request)
    {
        $this->treasurer($request);
        $entries = LedgerEntry::with(['project', 'member'])->when($request->query('filter') === 'liquidation', fn ($q) => $q->where('direction', 'debit')->where('status', 'Posted'))->latest('transaction_date')->latest('id')->paginate(30)->withQueryString();
        $projects = Project::whereIn('status', ['Approved', 'Ongoing'])->orderBy('title')->get();
        $members = User::where('status', 'active')->orderBy('name')->get();
        return view('chapter.ledger', compact('entries', 'projects', 'members'));
    }

    public function store(Request $request)
    {
        $this->treasurer($request);
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:ledger_entries,reference',
            'project_id' => 'nullable|integer|exists:projects,id', 'member_id' => 'nullable|integer|exists:users,id',
            'transaction_date' => 'required|date', 'type' => ['required', Rule::in(['Income', 'Expense', 'Disbursement', 'Adjustment credit', 'Adjustment debit'])],
            'category' => 'required|string|max:100', 'account' => 'required|string|max:150', 'counterparty' => 'required|string|max:200',
            'payment_method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])],
            'description' => 'required|string|max:5000', 'amount' => 'required|numeric|min:0.01|max:999999999.99',
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
            DB::transaction(function () use ($data) {
                if (!empty($data['project_id'])) {
                    $project = Project::lockForUpdate()->findOrFail($data['project_id']);
                    abort_unless(in_array($project->status, ['Approved', 'Ongoing']), 422, 'Transactions require an approved active project.');
                    if ($data['direction'] === 'debit' && round((float) $data['amount'], 2) > $project->remaining) throw ValidationException::withMessages(['amount' => 'This expense exceeds the available allocation. Increase the authorized allocation first.']);
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
                if ($entry->project_id) Project::lockForUpdate()->findOrFail($entry->project_id);
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
                ChapterService::audit('transaction_'.$data['action'], $entry, $before, $data['remarks']);
                if ($entry->member_due_id) ChapterService::notify([$entry->member_id], 'Dues payment updated', $entry->reference.' was '.$entry->status, route('dues', [], false));
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Transaction updated with audit history preserved.');
    }

    public function receipt(Request $request, LedgerEntry $entry)
    {
        abort_unless($request->user()->role === 'treasurer' || ($entry->member_due_id && $entry->member_id === $request->user()->id), 403);
        abort_unless($entry->document_path && Storage::disk('local')->exists($entry->document_path), 404);
        return Storage::disk('local')->download($entry->document_path, $entry->document_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function dues(Request $request)
    {
        $all = $request->routeIs('dues.manage');
        if ($all) $this->treasurer($request);
        $dues = MemberDue::with(['member', 'payments'])->when(!$all, fn ($q) => $q->where('member_id', $request->user()->id))->latest('period')->paginate(30);
        $members = $all ? User::where('status', 'active')->orderBy('name')->get() : collect();
        return view('chapter.dues', compact('dues', 'members', 'all'));
    }

    public function openDues(Request $request)
    {
        $this->treasurer($request);
        $data = $request->validate(['period' => 'required|date_format:Y-m', 'due_date' => 'required|date', 'amount' => 'required|numeric|min:0|max:9999999.99', 'member_id' => 'nullable|integer|exists:users,id', 'remarks' => 'required|string|max:5000']);
        DB::transaction(function () use ($data, $request) {
            $members = User::where('status', 'active')->when(!empty($data['member_id']), fn ($q) => $q->whereKey($data['member_id']))->lockForUpdate()->get();
            foreach ($members as $member) {
                $due = MemberDue::firstOrNew(['member_id' => $member->id, 'period' => $data['period']]);
                $before = $due->toArray();
                if ($due->exists && (float) $data['amount'] < $due->paid) throw ValidationException::withMessages(['amount' => 'The dues amount cannot be lower than payments already posted.']);
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
        $data = $request->validate(['reference' => 'required|string|max:100|unique:ledger_entries,reference', 'amount' => 'required|numeric|min:0.01|max:9999999.99', 'transaction_date' => 'required|date', 'payment_method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])], 'remarks' => 'nullable|string|max:5000', 'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        unset($data['receipt']);
        $path = $request->hasFile('receipt') ? $request->file('receipt')->store('financial-documents', 'local') : null;
        if ($path) { $data['document_path'] = $path; $data['document_name'] = $request->file('receipt')->getClientOriginalName(); }
        try {
            DB::transaction(function () use ($due, $data, $request) {
                $due = MemberDue::lockForUpdate()->findOrFail($due->id);
                if (round((float) $data['amount'], 2) > $due->balance) throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding dues balance.']);
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
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Type', 'Project', 'Description', 'Credit', 'Debit', 'Status']);
            foreach (LedgerEntry::with('project')->orderBy('transaction_date')->cursor() as $entry) {
                $safe = fn ($s) => preg_match('/^[=+@\\-]/', (string) $s) ? "'".$s : $s;
                fputcsv($out, array_map($safe, [$entry->reference, $entry->transaction_date?->format('Y-m-d'), $entry->type, $entry->project?->reference, $entry->description, $entry->direction === 'credit' ? $entry->amount : '', $entry->direction === 'debit' ? $entry->amount : '', $entry->status]));
            }
            fclose($out);
        }, 'jci-carmona-ledger.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

