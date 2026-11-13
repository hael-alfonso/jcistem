<?php

namespace App\Services;

use App\Models\{Project, ProjectReview, User};
use Illuminate\Support\Facades\DB;

class ProjectAppealService
{
    public function submit(Project $project, User $user, string $message): void
    {
        DB::transaction(function () use ($project, $user, $message) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($user->status === 'active' && $project->status === 'Cancelled'
                && ($project->created_by === $user->id || $project->chair_id === $user->id), 403);
            $cancellation = $project->reviews()->where('action', 'cancel')->latest('id')->first();
            abort_unless($cancellation, 409, 'The cancellation decision is missing.');
            abort_if($project->reviews()->where('action', 'appeal_submitted')->where('id', '>', $cancellation->id)->exists(), 409, 'An appeal has already been recorded for this cancellation.');

            ProjectReview::create([
                'project_id' => $project->id, 'user_id' => $user->id, 'action' => 'appeal_submitted',
                'from_status' => 'Cancelled', 'to_status' => 'Cancelled', 'comments' => $message,
                'snapshot' => $project->toArray(),
            ]);
            ChapterService::audit('project_appeal_submitted', $project, [], $message);
            ChapterService::notify(User::where('role', 'admin')->where('status', 'active')->pluck('id'), $project->title,
                'Cancellation appeal needs review', route('projects.show', $project, false).'#cancellation-decision', true);
        });
    }

    public function decide(Project $project, User $admin, string $decision, string $comments): void
    {
        DB::transaction(function () use ($project, $admin, $decision, $comments) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($admin->status === 'active' && $admin->role === 'admin' && $project->status === 'Cancelled', 403);
            $cancellation = $project->reviews()->where('action', 'cancel')->latest('id')->first();
            $appeal = $cancellation ? $project->reviews()->where('action', 'appeal_submitted')->where('id', '>', $cancellation->id)->latest('id')->first() : null;
            abort_unless($appeal && !$project->reviews()->whereIn('action', ['appeal_approved', 'appeal_declined'])->where('id', '>', $appeal->id)->exists(), 409);
            $before = $project->toArray();

            if ($decision === 'approve') {
                abort_unless($cancellation && $cancellation->from_status !== 'Cancelled', 409);
                $project->status = $cancellation->from_status;
                $project->archived_at = null;
                $project->save();
            }

            ProjectReview::create([
                'project_id' => $project->id, 'user_id' => $admin->id,
                'action' => $decision === 'approve' ? 'appeal_approved' : 'appeal_declined',
                'from_status' => 'Cancelled', 'to_status' => $project->status, 'comments' => $comments,
                'snapshot' => $before,
            ]);
            ChapterService::audit('project_appeal_'.$decision.'d', $project, $before, $comments);
            ChapterService::notify([$project->created_by, $project->chair_id], $project->title,
                $decision === 'approve' ? 'Cancellation appeal approved: project reopened' : 'Cancellation appeal declined: project remains closed',
                route('projects.show', $project, false).($decision === 'approve' ? '#project-cancellation-history' : '#cancellation-decision'), true);
        });
    }
}
