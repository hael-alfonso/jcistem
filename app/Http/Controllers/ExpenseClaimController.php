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
        $treasurer = $request->user()->role === 'treasurer';
        $claims = ExpenseClaim::with(['project', 'submitter', 'reviewer', 'ledgerEntry'])
            ->when(!$treasurer, fn ($query) => $query->where('submitted_by', $request->user()->id))
            ->when($request->query('status', 'pending') === 'pending', fn ($query) => $query->where('status', 'Pending'))
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(15)->withQueryString();

        return view('chapter.expense-claims', compact('claims', 'treasurer'));
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
