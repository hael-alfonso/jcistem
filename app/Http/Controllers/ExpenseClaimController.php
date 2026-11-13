<?php

namespace App\Http\Controllers;

use App\Models\{ExpenseClaim, LedgerEntry, Project, User};
use App\Services\ChapterService;
use App\Support\{ProjectBudget, ProjectFinanceGuard};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class ExpenseClaimController extends Controller
{
    public function index(Request $request)
    {
        // Treasurers review the chapter queue by default, while `mine=1`
        // opens the same personal claims view available to every user.
        $treasurer = $request->user()->role === 'treasurer' && ! $request->boolean('mine');
        $request->validate(['status' => ['nullable', Rule::in(['pending', 'all'])], 'project' => 'nullable|integer|exists:projects,id', 'q' => 'nullable|string|max:200', 'state' => 'nullable|in:Pending,Posted,Returned,Rejected,Void', 'from' => 'nullable|date_format:Y-m', 'to' => 'nullable|date_format:Y-m'.($request->filled('from') ? '|after_or_equal:from' : ''), 'mine' => 'nullable|boolean']);
        $filter = $request->query('status', $treasurer ? 'pending' : 'all');
        $selectedProject = $request->filled('project') ? Project::withTrashed()->findOrFail($request->integer('project')) : null;
        if ($selectedProject) abort_unless($treasurer || ChapterService::canView($selectedProject, $request->user()), 403);
        $claims = ExpenseClaim::with(['project', 'submitter', 'reviewer', 'ledgerEntry'])
            ->when(!$treasurer, fn ($query) => $query->where('submitted_by', $request->user()->id))
            ->when($selectedProject, fn ($query) => $query->where('project_id', $selectedProject->id))
            ->when($filter === 'pending', fn ($query) => $query->where('status', 'Pending'))
            ->when($filter === 'all' && $request->filled('state'), fn ($query) => $query->where('status', $request->query('state')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('spent_on', '>=', \App\Support\MonthFilter::start($request->query('from'))))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('spent_on', '<=', \App\Support\MonthFilter::end($request->query('to'))))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($match) => $match->where('description', 'like', '%'.$request->string('q').'%')
                ->orWhere('payee', 'like', '%'.$request->string('q').'%')
                ->orWhere('receipt_reference', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('project', fn ($project) => $project->where('title', 'like', '%'.$request->string('q').'%'))))
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(15)->withQueryString();

        $claimProjects = Project::withTrashed()->whereIn('id', ExpenseClaim::query()->when(!$treasurer, fn ($query) => $query->where('submitted_by', $request->user()->id))->distinct()->pluck('project_id'))->orderBy('title')->get(['id', 'title']);
        return view('chapter.expense-claims', compact('claims', 'claimProjects', 'treasurer', 'filter', 'selectedProject'));
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->canManage($request->user()), 403);
        $data = $request->validate([
            'budget_item_id' => 'required|uuid',
            'spent_on' => 'required|date|before_or_equal:today',
            'payee' => 'required|string|max:200',
            'receipt_reference' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $path = $request->file('receipt')->store('expense-claims', 'local');
        $name = $request->file('receipt')->getClientOriginalName();
        unset($data['receipt']);
        try {
            DB::transaction(function () use ($project, $request, $data, $path, $name) {
                $project = Project::lockForUpdate()->findOrFail($project->id);
                abort_unless($project->canManage($request->user()), 403);
                ProjectFinanceGuard::expenseLine($project, $data['budget_item_id'], $data['amount']);
                $claim = ExpenseClaim::create($data + [
                    'project_id' => $project->id,
                    'submitted_by' => $request->user()->id,
                    'receipt_path' => $path,
                    'receipt_name' => $name,
                    'status' => 'Pending',
                ]);
                $project->touch();
                ChapterService::audit('expense_claim_submitted', $claim);
                ChapterService::notify(User::where('status', 'active')->where('role', 'treasurer')->where('id', '!=', $request->user()->id)->pluck('id')->all(),
                    'Expense claim needs review', $project->title.' · PHP '.number_format((float) $claim->amount, 2), route('expense-claims.index', [], false), true);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return back()->with('success', 'Expense claim sent to the Treasurer for verification.');
    }

    public function update(Request $request, ExpenseClaim $claim)
    {
        abort_unless($claim->submitted_by === $request->user()->id && $claim->status === 'Returned'
            && $claim->project->canManage($request->user()), 403);
        $data = $request->validate([
            'budget_item_id' => 'required|uuid',
            'spent_on' => 'required|date|before_or_equal:today',
            'payee' => 'required|string|max:200',
            'receipt_reference' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        unset($data['receipt']);
        $path = $request->hasFile('receipt') ? $request->file('receipt')->store('expense-claims', 'local') : null;
        try {
            DB::transaction(function () use ($claim, $request, $data, $path) {
                $project = Project::lockForUpdate()->findOrFail($claim->project_id);
                $claim = ExpenseClaim::lockForUpdate()->findOrFail($claim->id);
                abort_unless($claim->submitted_by === $request->user()->id && $claim->status === 'Returned'
                    && $project->canManage($request->user()), 403);
                if (!$path && (!$claim->receipt_path || !Storage::disk('local')->exists($claim->receipt_path))) {
                    throw ValidationException::withMessages(['receipt' => 'Upload the receipt again before resubmitting.']);
                }
                ProjectFinanceGuard::expenseLine($project, $data['budget_item_id'], $data['amount']);
                $before = $claim->toArray();
                $claim->fill($data);
                if ($path) {
                    $claim->receipt_path = $path;
                    $claim->receipt_name = $request->file('receipt')->getClientOriginalName();
                }
                $claim->status = 'Pending';
                $claim->reviewed_by = null;
                $claim->reviewed_at = null;
                $claim->review_note = null;
                $claim->save();
                ChapterService::audit('expense_claim_resubmitted', $claim, $before);
                ChapterService::notify(User::where('status', 'active')->where('role', 'treasurer')
                    ->where('id', '!=', $request->user()->id)->pluck('id')->all(),
                    'Expense claim resubmitted', $project->title.' · PHP '.number_format((float) $claim->amount, 2),
                    route('expense-claims.index', [], false), true);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }

        return redirect()->route('expense-claims.index')->with('success', 'Corrected claim sent back to the Treasurer.');
    }

    public function review(Request $request, ExpenseClaim $claim)
    {
        abort_unless($request->user()->role === 'treasurer' && $request->user()->status === 'active', 403);
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'return', 'reject'])],
            'review_note' => 'required|string|max:5000',
            'account' => 'required_if:action,approve|nullable|string|max:150',
            'payment_method' => ['required_if:action,approve', 'nullable', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])],
        ]);
        DB::transaction(function () use ($claim, $request, $data) {
            $project = Project::lockForUpdate()->findOrFail($claim->project_id);
            $claim = ExpenseClaim::lockForUpdate()->findOrFail($claim->id);
            abort_unless($claim->status === 'Pending', 422, 'This claim already has a decision.');
            abort_if($claim->submitted_by === $request->user()->id || $project->chair_id === $request->user()->id, 403, 'An independent Treasurer must verify the Chair’s claim.');
            $before = $claim->toArray();
            if ($data['action'] === 'approve') {
                abort_unless(in_array($project->status, ['Plan Locked', 'Ongoing'], true), 422, 'Only active project claims may be posted.');
                ProjectFinanceGuard::expenseLine($project, $claim->budget_item_id, $claim->amount, $claim->id);
                if (!Storage::disk('local')->exists($claim->receipt_path)) throw ValidationException::withMessages(['receipt' => 'The original receipt is missing.']);
                $item = collect($project->budget_items)->firstWhere('id', $claim->budget_item_id);
                $reference = 'CLAIM-'.str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT);
                if (LedgerEntry::where('reference', $reference)->exists()) throw ValidationException::withMessages(['action' => 'This claim reference is already in the ledger. Contact the Administrator.']);
                $entry = LedgerEntry::create([
                    'reference' => $reference,
                    'project_id' => $project->id,
                    'budget_item_id' => $claim->budget_item_id,
                    'transaction_date' => $claim->spent_on,
                    'type' => 'Expense',
                    'direction' => 'debit',
                    'category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense',
                    'account' => $data['account'],
                    'counterparty' => $claim->payee,
                    'payment_method' => $data['payment_method'],
                    'description' => $claim->description,
                    'amount' => $claim->amount,
                    'remarks' => trim($data['review_note'].($claim->receipt_reference ? ' | Receipt: '.$claim->receipt_reference : '')),
                    'document_path' => $claim->receipt_path,
                    'document_name' => $claim->receipt_name,
                    'recorded_by' => $request->user()->id,
                    'status' => 'Posted',
                    'liquidation_status' => 'Pending',
                ]);
                $claim->ledger_entry_id = $entry->id;
                $claim->status = 'Posted';
                $project->touch();
                ChapterService::audit('transaction_posted_from_claim', $entry);
            } else {
                $claim->status = $data['action'] === 'return' ? 'Returned' : 'Rejected';
            }
            $claim->reviewed_by = $request->user()->id;
            $claim->reviewed_at = now();
            $claim->review_note = $data['review_note'];
            $claim->save();
            ChapterService::audit('expense_claim_'.$claim->status, $claim, $before, $data['review_note']);
            ChapterService::notify([$claim->submitted_by], 'Expense claim '.$claim->status,
                $project->title.' · PHP '.number_format((float) $claim->amount, 2), route('expense-claims.index', [], false), true);
        });

        return back()->with('success', 'Expense claim decision recorded.');
    }

    public function receipt(Request $request, ExpenseClaim $claim)
    {
        abort_unless($request->user()->role === 'treasurer' || $claim->submitted_by === $request->user()->id || $claim->project->chair_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($claim->receipt_path), 404);
        return Storage::disk('local')->response($claim->receipt_path, $claim->receipt_name, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
