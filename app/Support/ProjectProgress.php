<?php

namespace App\Support;

use App\Models\Project;
use Carbon\CarbonInterface;

final class ProjectProgress
{
    public static function summarize(Project $project, int $completed, int $total, ?CarbonInterface $today = null): array
    {
        $percent = $total > 0 ? (int) round(100 * $completed / $total) : 0;
        $percent = max(0, min(100, $percent));
        $due = $project->ends_on?->format('M d, Y');
        $result = [
            'percent' => $percent,
            'elapsed' => null,
            'pace' => $total === 0 ? 'No tasks yet' : ($completed >= $total ? 'All tasks complete' : 'Tasks in progress'),
            'due' => $due,
        ];

        // A time comparison only has meaning once the approved project is in delivery.
        if ($total === 0 || !in_array($project->status, ['Plan Locked', 'Ongoing', 'Completion Review'], true)
            || !$project->starts_on || !$project->ends_on) return $result;

        $start = $project->starts_on->toImmutable()->startOfDay();
        $end = $project->ends_on->toImmutable()->startOfDay();
        if ($end->lessThan($start)) return $result;
        $today = ($today ?? now())->toImmutable()->startOfDay();

        $days = (int) $start->diffInDays($end) + 1;
        $elapsedDays = $today->lessThan($start) ? 0 : min($days, (int) $start->diffInDays($today) + 1);
        $elapsed = (int) round(100 * $elapsedDays / $days);
        $result['elapsed'] = $elapsed;
        $result['pace'] = match (true) {
            $completed >= $total => 'All tasks complete',
            $today->lessThan($start) => 'Starts soon',
            $today->greaterThan($end) => 'Past target date',
            $percent + 10 < $elapsed => 'Behind pace',
            $percent > $elapsed + 10 => 'Ahead of pace',
            default => 'On pace',
        };

        return $result;
    }
}
