<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

final class ProjectLaunchService
{
    public static function launchIfReady(Project $project): bool
    {
        return DB::transaction(function () use ($project): bool {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            if (!in_array($project->status, ['Approved', 'Plan Locked'], true)
                || !$project->plan_locked_at || !$project->proposal_reviewed_at
                || !$project->starts_on || $project->starts_on->toDateString() > now('Asia/Manila')->toDateString()
                || !$project->tasks()->get(['id', 'assignees'])->contains(fn ($task) => count($task->assignees ?? []) > 0)) return false;
            if (!$project->decisions()->where('stage', 'proposal')->where('decision', 'approved')->exists()) return false;

            $before = $project->toArray();
            $project->status = 'Ongoing';
            $project->save();
            ChapterService::audit('launch_scheduled', $project, $before, null, true);
            $assignees = $project->tasks()->get()->flatMap(fn ($task) => $task->assignees ?? [])->all();
            ChapterService::notify(array_merge([$project->created_by, $project->chair_id], $assignees),
                'Project launched: '.$project->title, 'Implementation and task progress are now open.',
                route('projects.show', $project, false).'#delivery', true);
            return true;
        });
    }

    public static function launchDue(): int
    {
        $count = 0;
        Project::whereIn('status', ['Approved', 'Plan Locked'])->whereNotNull('plan_locked_at')
            ->whereNotNull('proposal_reviewed_at')->whereDate('starts_on', '<=', now('Asia/Manila')->toDateString())
            ->chunkById(100, function ($projects) use (&$count): void {
                foreach ($projects as $project) if (self::launchIfReady($project)) $count++;
            });
        return $count;
    }
}
