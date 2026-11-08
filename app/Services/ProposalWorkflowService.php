<?php

namespace App\Services;

use App\Models\{BodDecision, Project, ProjectReview, User};
use App\Support\{ProjectFlow, ProjectSubmission};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProposalWorkflowService
{
    public function transition(Project $project, User $user, string $action, ?string $comments, array $decision = []): Project
    {
        return DB::transaction(function () use ($project, $user, $action, $comments, $decision) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($user->status === 'active', 403);
            $before = $project->toArray();
            $from = $project->status;
            $owner = $project->created_by === $user->id;
            $independent = !$owner && $project->chair_id !== $user->id;
            $admin = $user->role === 'admin';
            $treasurer = $user->role === 'treasurer';
            $chair = $project->isChair($user);
            switch ($action) {
                case 'cancel':
                    abort_unless($project->canCancel($user), 403);
                    $this->comments($comments);
                    foreach ($project->fundReleases()->where('status', 'Pending')->lockForUpdate()->get() as $release) {
                        $releaseBefore = $release->toArray();
                        $release->update(['status' => 'Cancelled', 'cancel_note' => 'Project cancelled: '.$comments, 'cancelled_at' => now()]);
                        ChapterService::audit('fund_release_cancelled', $release, $releaseBefore, $comments);
                    }
                    $project->status = 'Cancelled';
                    $project->archived_at = now();
                    break;
                case 'restart_review':
                    abort_unless(($admin || $owner) && !$project->decisions()->exists()
                        && in_array($from, ['Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Submitted for Formal Approval', 'Approved'], true)
                        && !$project->plan_locked_at && !$project->tasks()->exists() && !$project->allocations()->exists()
                        && !$project->transactions()->exists() && !$project->expenseClaims()->exists()
                        && !$project->fundReleases()->exists(), 403);
                    $this->comments($comments);
                    $project->chair_id = null;
                    $project->plan_locked_at = null;
                    $project->budget_reviewed_at = null;
                    $project->budget_reviewed_by = null;
                    $project->proposal_submitted_revision = null;
                    $project->proposal_budget_reviewed_at = null;
                    $project->proposal_budget_reviewed_by = null;
                    $project->proposal_reviewed_at = null;
                    $project->proposal_reviewed_by = null;
                    $project->status = 'Admin Review';
                    break;
                case 'submit_concept':
                    abort_unless($owner && in_array($from, ['Draft Concept', 'Needs Revision']), 403);
                    if ($issues = ProjectSubmission::issues($project, 'concept')) throw ValidationException::withMessages($issues);
                    $project->budget_reviewed_at = null;
                    $project->budget_reviewed_by = null;
                    $project->status = 'Admin Review';
                    break;
                case 'endorse': case 'revise_concept':
                    abort_unless($admin && in_array($from, ['Admin Review', 'Submitted for President Review']), 403);
                    $this->comments($comments);
                    $project->status = $action === 'endorse' ? 'Treasurer Review' : 'Needs Revision';
                    break;
                case 'budget_review': case 'revise_budget':
                    abort_unless($treasurer && $independent && $from === 'Treasurer Review', 403);
                    $this->comments($comments);
                    $project->status = $action === 'budget_review' ? 'BOD Agenda' : 'Needs Revision';
                    $project->budget_reviewed_at = $action === 'budget_review' ? now() : null;
                    $project->budget_reviewed_by = $action === 'budget_review' ? $user->id : null;
                    break;
                case 'approve': case 'reject': case 'defer':
                    abort_unless($admin && $from === 'BOD Agenda', 403);
                    $this->comments($comments);
                    if (empty($decision['meeting_date']) || empty($decision['evidence_path'])) {
                        $this->invalid('Record the meeting date and signed BOD decision evidence.');
                    }
                    if (!Storage::disk('local')->exists($decision['evidence_path'])) $this->invalid('The signed BOD evidence file is missing.');
                    if (!$project->budget_reviewed_at) $this->invalid('Treasurer review is required for every proposal.');
                    BodDecision::create([
                        'project_id' => $project->id, 'recorded_by' => $user->id,
                        'stage' => 'concept',
                        'meeting_date' => $decision['meeting_date'],
                        'evidence_path' => $decision['evidence_path'], 'comments' => $comments,
                        'decision' => ['approve' => 'approved', 'reject' => 'rejected', 'defer' => 'deferred'][$action],
                    ]);
                    $project->status = ['approve' => 'Approved', 'reject' => 'Not Approved', 'defer' => 'BOD Agenda'][$action];
                    if ($action === 'reject') $project->archived_at = now();
                    if ($action === 'approve') {
                        $project->chair_id = $project->created_by;
                        $project->plan_locked_at = null;
                    }
                    break;
                case 'submit_full_proposal':
                    abort_unless($chair && $from === 'Approved' && !$project->proposal_reviewed_at, 403);
                    if ($issues = ProjectSubmission::issues($project, 'proposal')) throw ValidationException::withMessages($issues);
                    $project->proposal_submitted_revision = $project->revision;
                    $project->proposal_budget_reviewed_at = null;
                    $project->proposal_budget_reviewed_by = null;
                    $project->status = 'Submitted for Formal Approval';
                    break;
                case 'review_full_budget':
                    abort_unless($treasurer && $independent && $from === 'Submitted for Formal Approval' && !$project->proposal_budget_reviewed_at, 403);
                    $this->comments($comments);
                    if ($project->proposal_submitted_revision !== $project->revision) $this->invalid('The proposal changed after submission. Ask the Chair to submit it again.');
                    $project->proposal_budget_reviewed_at = now();
                    $project->proposal_budget_reviewed_by = $user->id;
                    break;
                case 'return_full_proposal':
                    abort_unless(($admin || ($treasurer && $independent)) && $from === 'Submitted for Formal Approval', 403);
                    $this->comments($comments);
                    $project->proposal_submitted_revision = null;
                    $project->proposal_budget_reviewed_at = null;
                    $project->proposal_budget_reviewed_by = null;
                    $project->status = 'Approved';
                    break;
                case 'approve_full_proposal':
                    abort_unless($admin && $from === 'Submitted for Formal Approval', 403);
                    $this->comments($comments);
                    if (!$project->proposal_budget_reviewed_at || $project->proposal_submitted_revision !== $project->revision) $this->invalid('The Treasurer must verify this exact proposal revision before final approval.');
                    if (empty($decision['meeting_date']) || empty($decision['evidence_path'])) $this->invalid('Record the BOD meeting date and signed evidence.');
                    if (!Storage::disk('local')->exists($decision['evidence_path'])) $this->invalid('The signed BOD evidence file is missing.');
                    BodDecision::create([
                        'project_id' => $project->id, 'recorded_by' => $user->id, 'stage' => 'proposal',
                        'meeting_date' => $decision['meeting_date'],
                        'evidence_path' => $decision['evidence_path'], 'comments' => $comments, 'decision' => 'approved',
                    ]);
                    $project->proposal_reviewed_at = now();
                    $project->proposal_reviewed_by = $user->id;
                    $project->status = 'Approved';
                    break;
                case 'lock_plan':
                    abort_unless($chair && $from === 'Approved' && !$project->plan_locked_at, 403);
                    if (!$project->proposal_reviewed_at || $project->proposal_submitted_revision !== $project->revision || !$project->decisions()->where('stage', 'proposal')->where('decision', 'approved')->exists()) $this->invalid('The full proposal needs Treasurer verification and a recorded BOD approval before the plan can be locked.');
                    if ($issues = ProjectSubmission::issues($project, 'proposal')) throw ValidationException::withMessages($issues);
                    $project->plan_locked_at = now();
                    $project->status = 'Plan Locked';
                    break;
                case 'start':
                    abort_unless($chair && $from === 'Plan Locked' && $project->plan_locked_at, 403);
                    $project->status = 'Ongoing';
                    break;
                case 'request_completion':
                    abort_unless($chair && $from === 'Ongoing', 403);
                    if (!$project->tasks()->exists()) $this->invalid('Add and complete at least one project task before preparing final reports.');
                    if ($project->tasks()->where('status', '!=', 'Completed')->exists()) $this->invalid('Complete all project tasks before reporting.');
                    if ($project->expenseClaims()->where('status', 'Pending')->exists()) $this->invalid('Wait for the Treasurer to verify or return all pending expense claims before reporting.');
                    if ($project->fundReleases()->where('status', 'Pending')->exists()) $this->invalid('Wait for the Project Chair to confirm or decline every pending fund release before reporting.');
                    if ($project->allocations()->whereNull('budget_item_id')->where('amount', '>', 0)->exists()
                        || $project->transactions()->where('status', 'Posted')->where('direction', 'debit')->whereNull('budget_item_id')->exists()) {
                        $this->invalid('Reconcile older allocations and posted expenses to approved budget lines before reporting.');
                    }
                    $project->status = 'Completion Review';
                    break;
                // Independent report approvals complete the project; archiving is separate.
                default:
                    abort(422, 'This transition is not part of the current workflow.');
            }
            $project->save();
            ProjectReview::create(['project_id' => $project->id, 'user_id' => $user->id, 'action' => $action, 'from_status' => $from, 'to_status' => $project->status, 'comments' => $comments, 'snapshot' => $before]);
            ChapterService::audit($action, $project, $before, $comments);
            $roles = match ($project->status) { 'Admin Review' => ['admin'], 'Treasurer Review' => ['treasurer'], 'BOD Agenda' => ['admin', 'bod', 'treasurer'], 'Submitted for Formal Approval' => $action === 'review_full_budget' ? ['admin'] : ['admin', 'treasurer'], default => [] };
            $assignees = $action === 'cancel' ? $project->tasks()->get()->flatMap(fn ($task) => $task->assignees ?? [])->all() : [];
            ChapterService::notify(array_merge([$project->created_by, $project->chair_id], $assignees, User::where('status', 'active')->whereIn('role', $roles)->pluck('id')->all()), $project->title, 'Project status: '.ProjectFlow::statusLabel($project), $project->status === 'Cancelled' ? null : route('projects.show', $project, false), in_array($project->status, ['Admin Review', 'Treasurer Review', 'BOD Agenda', 'Submitted for Formal Approval', 'Not Approved', 'Returned for Revision', 'Completion Review', 'Cancelled'], true));
            return $project;
        });
    }

    private function comments(?string $comments): void
    {
        if (!trim($comments ?? '')) $this->invalid('Review comments are required.');
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['action' => $message]);
    }
}
