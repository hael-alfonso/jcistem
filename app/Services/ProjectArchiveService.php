<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectArchiveService
{
    public static function archiveDue(): int
    {
        $cutoff = now()->subDays(7);
        $archived = 0;

        Project::where('status', 'Completed')->whereNull('archived_at')
            ->whereNotNull('completed_at')->where('completed_at', '<=', $cutoff)
            ->chunkById(100, function ($projects) use ($cutoff, &$archived): void {
                foreach ($projects as $candidate) {
                    DB::transaction(function () use ($candidate, $cutoff, &$archived): void {
                        $project = Project::lockForUpdate()->find($candidate->id);
                        if (!$project || $project->status !== 'Completed' || $project->archived_at
                            || !$project->completed_at || $project->completed_at->greaterThan($cutoff)) return;

                        $before = $project->toArray();
                        $project->update(['archived_at' => now()]);
                        ChapterService::audit('project_auto_archived', $project, $before, 'Seven days after completion.', system: true);
                        $archived++;
                    });
                }
            });

        Project::where('status', 'Cancelled')->whereNull('archived_at')
            ->whereHas('reviews', fn ($reviews) => $reviews->where('action', 'cancel')->where('created_at', '<=', $cutoff))
            ->chunkById(100, function ($projects) use ($cutoff, &$archived): void {
                foreach ($projects as $candidate) {
                    DB::transaction(function () use ($candidate, $cutoff, &$archived): void {
                        $project = Project::lockForUpdate()->find($candidate->id);
                        if (!$project || $project->status !== 'Cancelled' || $project->archived_at) return;
                        $cancellation = $project->reviews()->where('action', 'cancel')->latest('id')->first();
                        if (!$cancellation || $cancellation->created_at->greaterThan($cutoff)) return;
                        $appeal = $project->reviews()->where('action', 'appeal_submitted')
                            ->where('id', '>', $cancellation->id)->latest('id')->first();
                        if ($appeal && !$project->reviews()->whereIn('action', ['appeal_approved', 'appeal_declined'])
                            ->where('id', '>', $appeal->id)->exists()) return;

                        $before = $project->toArray();
                        $project->update(['archived_at' => now()]);
                        ChapterService::audit('project_auto_archived', $project, $before,
                            'Seven days after cancellation; no appeal is awaiting review.', system: true);
                        $archived++;
                    });
                }
            });

        return $archived;
    }
}
