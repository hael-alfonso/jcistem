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

        return $archived;
    }
}
