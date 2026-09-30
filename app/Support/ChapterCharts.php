<?php

namespace App\Support;

use App\Models\{BudgetAllocation, LedgerEntry};
use Illuminate\Support\Collection;

class ChapterCharts
{
    public static function projectMix(Collection $projects): array
    {
        return [
            'total' => $projects->count(),
            'statuses' => $projects->countBy('status')->sortDesc(),
            'areas' => $projects->countBy('area')->sortDesc(),
        ];
    }

    public static function finances(Collection $projects): array
    {
        $ids = $projects->pluck('id');

        $allocations = BudgetAllocation::query()
            ->whereIn('project_id', $ids)
            ->selectRaw('project_id, SUM(amount) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        // Project spending follows Project::getSpentAttribute: posted debit entries only.
        $postedDebits = LedgerEntry::query()
            ->whereIn('project_id', $ids)
            ->where('status', 'Posted')
            ->where('direction', 'debit');

        $spending = (clone $postedDebits)
            ->selectRaw('project_id, SUM(amount) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        $rows = $projects->map(function ($project) use ($allocations, $spending) {
            $allocated = round((float) ($allocations[$project->id] ?? 0), 2);
            $spent = round((float) ($spending[$project->id] ?? 0), 2);

            return [
                'project' => $project,
                'allocated' => $allocated,
                'spent' => $spent,
                'remaining' => round($allocated - $spent, 2),
                'utilization' => $allocated > 0 ? round(100 * $spent / $allocated, 1) : 0,
            ];
        });

        $firstMonth = today()->startOfMonth()->subMonths(5);
        $monthlyTotals = (clone $postedDebits)
            ->where('transaction_date', '>=', $firstMonth->toDateString())
            ->where('transaction_date', '<', today()->addDay()->toDateString())
            ->get(['transaction_date', 'amount'])
            ->groupBy(fn ($entry) => $entry->transaction_date->format('Y-m'))
            ->map(fn ($entries) => round((float) $entries->sum('amount'), 2));

        $months = collect(range(0, 5))->map(function ($offset) use ($firstMonth, $monthlyTotals) {
            $date = $firstMonth->copy()->addMonths($offset);
            return [
                'label' => $date->format('M Y'),
                'amount' => (float) ($monthlyTotals[$date->format('Y-m')] ?? 0),
            ];
        });

        return [
            'rows' => $rows,
            'allocated' => round((float) $rows->sum('allocated'), 2),
            'spent' => round((float) $rows->sum('spent'), 2),
            'maxProjectAmount' => max(1, (float) $rows->max('allocated'), (float) $rows->max('spent')),
            'months' => $months,
            'maxMonthlyAmount' => max(1, (float) $months->max('amount')),
        ];
    }
}
