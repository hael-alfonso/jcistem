<?php

namespace App\Http\Controllers;

use App\Models\{LedgerEntry, Project, ProjectFundRelease, User};
use App\Services\ChapterService;
use App\Support\{ProjectBudget, ProjectFinanceGuard};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class FundReleaseController extends Controller
{
    private function treasurer(Request $request): void
    {
        abort_unless($request->user()->status === 'active' && $request->user()->role === 'treasurer', 403);
    }

    public function store(Request $request, Project $project)
    {
        $this->treasurer($request);
        $data = $request->validate([
            'budget_item_id' => 'required|uuid',
            'release_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'account' => 'required|string|max:150',
            'payment_method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'])],
            'transfer_reference' => 'nullable|string|max:150',
            'purpose' => 'required|string|max:5000',
        ]);

        DB::transaction(function () use ($project, $request, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($project->chair_id && $project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true), 422, 'Fund releases require a locked, active project plan and an assigned Chair.');
            abort_if($project->chair_id === $request->user()->id, 403, 'A Project Chair cannot initiate their own fund release.');
            ProjectFinanceGuard::expenseLine($project, $data['budget_item_id'], $data['amount']);

            $release = ProjectFundRelease::create($data + [
                'project_id' => $project->id,
                'chair_id' => $project->chair_id,
                'initiated_by' => $request->user()->id,
                'status' => 'Pending',
            ]);
            $release->update(['reference' => 'FUND-'.$release->release_date->format('Y').'-'.str_pad((string) $release->id, 6, '0', STR_PAD_LEFT)]);
            $project->touch();
            ChapterService::audit('fund_release_requested', $release);
            ChapterService::notify([$project->chair_id], 'Confirm project fund receipt', $project->title.' · PHP '.number_format((float) $release->amount, 2).' · '.$release->reference, route('projects.show', $project, false).'#budget', true);
        });

        return redirect()->to(route('projects.show', $project).'#budget')->with('success', 'Fund release sent to the Project Chair for receipt confirmation.');
    }

    public function respond(Request $request, ProjectFundRelease $fundRelease)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'decline'])],
            'chair_note' => 'required_if:action,decline|nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $fundRelease, $data) {
            $project = Project::lockForUpdate()->findOrFail($fundRelease->project_id);
            $release = ProjectFundRelease::lockForUpdate()->findOrFail($fundRelease->id);
            abort_unless($request->user()->status === 'active' && $release->chair_id === $request->user()->id && $project->chair_id === $request->user()->id, 403);
            abort_unless($release->status === 'Pending', 422, 'This fund release already has a response.');
            $before = $release->toArray();

            if ($data['action'] === 'decline') {
                $release->update(['status' => 'Declined', 'chair_note' => trim($data['chair_note']), 'declined_at' => now()]);
                ChapterService::audit('fund_release_declined', $release, $before, $data['chair_note']);
                ChapterService::notify([$release->initiated_by], 'Project fund release declined', $project->title.' · '.$release->reference.' · '.$data['chair_note'], route('projects.show', $project, false).'#budget', true);
                return;
            }

            abort_unless($project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true), 422, 'This project is no longer open for fund release confirmation.');
            $item = ProjectFinanceGuard::expenseLine($project, $release->budget_item_id, $release->amount, null, $release->id);
            if (LedgerEntry::where('reference', $release->reference)->exists()) {
                throw ValidationException::withMessages(['action' => 'This release is already recorded in the ledger.']);
            }
            $chair = User::withTrashed()->findOrFail($release->chair_id);
            $remarks = collect([
                'Receipt confirmed by the Project Chair on '.now()->format('M d, Y g:i A').'.',
                $release->transfer_reference ? 'Payment reference: '.$release->transfer_reference.'.' : null,
                trim($data['chair_note'] ?? '') ?: null,
            ])->filter()->join(' ');
            $entry = LedgerEntry::create([
                'reference' => $release->reference,
                'project_id' => $project->id,
                'budget_item_id' => $release->budget_item_id,
                'transaction_date' => $release->release_date,
                'type' => 'Disbursement',
                'direction' => 'debit',
                'category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense',
                'account' => $release->account,
                'counterparty' => $chair->name,
                'payment_method' => $release->payment_method,
                'description' => 'Cash advance for '.$item['description'].': '.$release->purpose,
                'amount' => $release->amount,
                'remarks' => $remarks,
                'recorded_by' => $release->initiated_by,
                'status' => 'Posted',
                'liquidation_status' => 'Pending',
            ]);
            $release->update(['status' => 'Confirmed', 'chair_note' => trim($data['chair_note'] ?? '') ?: null, 'confirmed_at' => now(), 'ledger_entry_id' => $entry->id]);
            $project->touch();
            ChapterService::audit('transaction_posted_from_fund_release', $entry);
            ChapterService::audit('fund_release_confirmed', $release, $before, $data['chair_note'] ?? null);
            ChapterService::notify([$release->initiated_by], 'Project fund receipt confirmed', $project->title.' · '.$release->reference.' · PHP '.number_format((float) $release->amount, 2), route('projects.show', $project, false).'#budget', true);
        });

        $message = $data['action'] === 'confirm' ? 'Fund receipt confirmed and posted to the official ledger.' : 'The Treasurer was notified that the fund release was declined.';
        return redirect()->to(route('projects.show', $fundRelease->project_id).'#budget')->with('success', $message);
    }

    public function cancel(Request $request, ProjectFundRelease $fundRelease)
    {
        $this->treasurer($request);
        $data = $request->validate(['cancel_note' => 'required|string|max:2000']);

        DB::transaction(function () use ($fundRelease, $data) {
            $release = ProjectFundRelease::lockForUpdate()->findOrFail($fundRelease->id);
            abort_unless($release->status === 'Pending', 422, 'Only a pending fund release can be cancelled.');
            $before = $release->toArray();
            $release->update(['status' => 'Cancelled', 'cancel_note' => trim($data['cancel_note']), 'cancelled_at' => now()]);
            ChapterService::audit('fund_release_cancelled', $release, $before, $data['cancel_note']);
            ChapterService::notify([$release->chair_id], 'Project fund release cancelled', $release->project->title.' · '.$release->reference, route('projects.show', $release->project_id, false).'#budget', true);
        });

        return redirect()->to(route('projects.show', $fundRelease->project_id).'#budget')->with('success', 'Pending fund release cancelled. No ledger transaction was created.');
    }
}
