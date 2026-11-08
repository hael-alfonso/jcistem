<?php

namespace App\Support;

use App\Models\Project;
use Carbon\CarbonInterface;

final class ProjectProgress
{
    public static function summarize(Project $project, int $completed, int $total, ?CarbonInterface $today = null): array
    {
        $taskPercent = $total > 0 ? (int) round(100 * $completed / $total) : 0;
        $taskPercent = max(0, min(100, $taskPercent));
        $status = $project->status;
        $percent = match ($status) {
            'Draft Concept', 'Needs Revision' => 5,
            'Admin Review', 'Submitted for President Review' => 12,
            'Treasurer Review' => 22,
            'BOD Agenda' => 32,
            'Approved' => $project->proposal_reviewed_at ? 48 : 42,
            'Submitted for Formal Approval' => 44,
            'Plan Locked' => 50,
            'Ongoing' => 50 + (int) round(35 * $taskPercent / 100),
            'Completion Review' => 85 + self::reportCredit($project),
            'Completed', 'Archived' => 100,
            default => 0,
        };
        $due = $project->ends_on?->format('M d, Y');
        $result = [
            'percent' => $percent,
            'elapsed' => null,
            'pace' => match (true) {
                in_array($status, ['Completed', 'Archived'], true) => 'Project closed',
                $status === 'Completion Review' => 'Final reports under review',
                $status === 'Ongoing' && $total === 0 => 'No tasks yet',
                $status === 'Ongoing' && $completed >= $total => 'Implementation complete',
                $status === 'Ongoing' => 'Implementation in progress',
                in_array($status, ['Not Approved', 'Declined'], true) => 'Not approved',
                $status === 'Cancelled' => 'Project cancelled',
                default => 'Awaiting next stage',
            },
            'due' => $due,
        ];

        // A time comparison only has meaning once the approved project is in delivery.
        if ($total === 0 || $status !== 'Ongoing'
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
            $taskPercent + 10 < $elapsed => 'Behind pace',
            $taskPercent > $elapsed + 10 => 'Ahead of pace',
            default => 'On pace',
        };

        return $result;
    }

    private static function reportCredit(Project $project): int
    {
        $reports = $project->relationLoaded('reports') ? $project->reports : $project->reports()->get();
        $credit = 0;
        foreach (['Completion', 'Liquidation'] as $type) {
            if ($reports->where('type', $type)->sortByDesc('id')->first()?->status === 'Reviewed') $credit += 7;
        }
        return $credit;
    }
}
