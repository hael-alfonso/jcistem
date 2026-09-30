<?php
namespace App\Services;

use App\Models\{AuditLog, Notification, Project, ProjectReview, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\ChapterForms;

class ChapterService
{
    public static function audit(string $action, Model $record, array $before = [], ?string $remarks = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(), 'action' => $action,
            'object_type' => class_basename($record), 'object_id' => $record->getKey(),
            'before' => self::redact($before), 'after' => self::redact($record->toArray()), 'remarks' => $remarks,
        ]);
    }

    private static function redact(array $data): array
    {
        return array_diff_key($data, array_flip(['password', 'remember_token', 'profile', 'document_path', 'path']));
    }

    public static function notify(iterable $users, string $title, string $body, string $url): void
    {
        foreach (collect($users)->filter()->unique()->values() as $id) {
            Notification::create(['user_id' => $id, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
    }

    public static function canView(Project $project, User $user): bool
    {
        return in_array($project->status, Project::APPROVED, true)
            || $project->created_by === $user->id
            || in_array($user->role, ['admin', 'bod', 'treasurer'], true);
    }

    public static function visibleProjects(User $user)
    {
        return Project::query()->when($user->role === 'member', fn ($q) => $q->where(fn ($q) =>
            $q->whereIn('status', Project::APPROVED)->orWhere('created_by', $user->id)));
    }

    public static function requireFields(array $data, array $labels, string $prefix): void
    {
        $errors = [];
        foreach ($labels as $key => $label) {
            if (trim((string) ($data[$key] ?? '')) === '') $errors[$prefix.'.'.$key] = $label.' is required before submission.';
        }
        if ($errors) throw ValidationException::withMessages($errors);
    }

    public function transition(Project $project, User $user, string $action, ?string $comments, ?int $chairId = null): Project
    {
        return DB::transaction(function () use ($project, $user, $action, $comments, $chairId) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $before = $project->toArray();
            $from = $project->status;
            $allowed = false;
            $to = $from;
            switch ($action) {
                case 'submit_concept':
                    $allowed = $project->created_by === $user->id && in_array($from, ['Draft Concept', 'Needs Revision']);
                    self::requireFields($project->concept ?? [], ChapterForms::CONCEPT, 'concept');
                    if (!$project->starts_on || !$project->venue) throw ValidationException::withMessages(['project' => 'Set a proposed date and venue before submitting.']);
                    $to = 'Submitted for President Review'; break;
                case 'endorse': case 'revise_concept': case 'decline':
                    $allowed = in_array($user->role, ['admin', 'bod']) && $user->concept_reviewer && $from === 'Submitted for President Review';
                    $to = ['endorse' => 'Endorsed for Development', 'revise_concept' => 'Needs Revision', 'decline' => 'Declined'][$action]; break;
                case 'start_proposal':
                    $allowed = $project->created_by === $user->id && $from === 'Endorsed for Development';
                    $c = $project->concept ?? [];
                    $project->proposal = ['rationale' => $c['background'] ?? '', 'objectives' => $c['objective'] ?? '', 'beneficiaries' => $c['beneficiaries'] ?? '', 'description' => $c['approach'] ?? '', 'partners' => $c['partners'] ?? '', 'resources' => $c['resources'] ?? '', 'outputs' => $c['benefit'] ?? '', 'risks' => $c['risks'] ?? ''];
                    $to = 'Full Proposal Draft'; break;
                case 'submit_proposal':
                    $allowed = $project->created_by === $user->id && in_array($from, ['Full Proposal Draft', 'Returned for Revision']);
                    self::requireFields($project->proposal ?? [], ChapterForms::PROPOSAL, 'proposal');
                    $project->budget_reviewed_at = null; $project->budget_reviewed_by = null;
                    $to = 'Submitted for Formal Approval'; break;
                case 'budget_review':
                    $allowed = $user->role === 'treasurer' && $from === 'Submitted for Formal Approval';
                    $project->budget_reviewed_at = now(); $project->budget_reviewed_by = $user->id; break;
                case 'approve': case 'revise_proposal': case 'reject':
                    $allowed = in_array($user->role, ['admin', 'bod']) && $user->proposal_reviewer && $from === 'Submitted for Formal Approval';
                    if ($action === 'approve' && $project->proposed_budget > 0 && !$project->budget_reviewed_at) throw ValidationException::withMessages(['action' => 'Treasurer budget review is required before approval.']);
                    $to = ['approve' => 'Approved', 'revise_proposal' => 'Returned for Revision', 'reject' => 'Not Approved'][$action]; break;
                case 'assign_chair':
                    $allowed = ($user->role === 'admin' || ($user->role === 'bod' && $user->proposal_reviewer)) && in_array($from, ['Approved', 'Ongoing']);
                    $chair = User::where('status', 'active')->findOrFail($chairId);
                    $project->chair_id = $chair->id; break;
                case 'start':
                    $allowed = $project->chair_id === $user->id && $from === 'Approved';
                    $to = 'Ongoing'; break;
                case 'request_completion':
                    $allowed = $project->chair_id === $user->id && $from === 'Ongoing';
                    if ($project->tasks()->where('status', '!=', 'Completed')->exists()) throw ValidationException::withMessages(['action' => 'Complete outstanding tasks before requesting completion.']);
                    if (!$project->reports()->where('type', 'Completion')->whereIn('status', ['Submitted', 'Reviewed'])->exists()) throw ValidationException::withMessages(['action' => 'Submit a completion report first.']);
                    $to = 'Completion Review'; break;
                case 'complete': case 'return_completion':
                    $allowed = $user->role === 'admin' && $from === 'Completion Review';
                    if ($action === 'complete' && $project->transactions()->where('status', 'Posted')->where('liquidation_status', 'Pending')->exists()) throw ValidationException::withMessages(['action' => 'Resolve pending liquidation before completion.']);
                    $to = $action === 'complete' ? 'Completed' : 'Ongoing'; break;
                case 'archive':
                    $allowed = $user->role === 'admin' && in_array($from, ['Completed', 'Declined', 'Not Approved']);
                    $to = 'Archived'; break;
                default: abort(422, 'Unknown project action.');
            }
            abort_unless($allowed, 403, 'This action is not available to your account at this stage.');
            if (in_array($action, ['endorse', 'revise_concept', 'decline', 'budget_review', 'approve', 'revise_proposal', 'reject', 'return_completion']) && !trim($comments ?? '')) throw ValidationException::withMessages(['comments' => 'Review comments are required.']);
            $project->status = $to;
            $project->save();
            ProjectReview::create(['project_id' => $project->id, 'user_id' => $user->id, 'action' => $action, 'from_status' => $from, 'to_status' => $to, 'comments' => $comments, 'snapshot' => $before]);
            self::audit($action, $project, $before, $comments);
            $recipients = [$project->created_by, $project->chair_id];
            if ($to === 'Submitted for President Review') $recipients = array_merge($recipients, User::where('concept_reviewer', true)->whereIn('role', ['admin', 'bod'])->pluck('id')->all());
            if ($to === 'Submitted for Formal Approval') $recipients = array_merge($recipients, User::where('proposal_reviewer', true)->orWhere('role', 'treasurer')->pluck('id')->all());
            if ($to === 'Completion Review') $recipients = array_merge($recipients, User::where('role', 'admin')->pluck('id')->all());
            self::notify($recipients, $project->title, str_replace('_', ' ', ucfirst($action)).': '.$to, route('projects.show', $project, false));
            return $project;
        });
    }
}

