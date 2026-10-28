<?php

namespace App\Services;

use App\Models\Project;

class ReportWorkflowService
{
    // Caller holds the project lock; a returned report never resets the other approval.
    public static function archiveIfReady(Project $project): void
    {
        if ($project->status !== 'Completion Review' || $project->tasks()->where('status', '!=', 'Completed')->exists()) return;
        foreach (['Completion' => 'admin', 'Liquidation' => 'treasurer'] as $type => $role) {
            $report = $project->reports()->where('type', $type)->latest('id')->first();
            if (!$report || $report->status !== 'Reviewed' || !$report->reviewed_by || !$report->reviewed_at || in_array($report->reviewed_by, [$report->created_by, $project->chair_id], true)) return;
            if (!\App\Models\User::whereKey($report->reviewed_by)->where('role', $role)->exists()) return;
        }
        $before = $project->toArray();
        $project->update(['status' => 'Archived']);
        ChapterService::audit('project_archived', $project, $before, 'Independent completion and liquidation approvals recorded.');
        ChapterService::notify([$project->created_by, $project->chair_id], 'Project archived', $project->title, route('projects.show', $project, false));
    }
}
