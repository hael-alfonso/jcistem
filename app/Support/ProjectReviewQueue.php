<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ProjectReviewQueue
{
    public static function apply(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query->where(function (Builder $projects): void {
                $projects->whereIn('status', ['Admin Review', 'BOD Agenda'])
                    ->orWhere(fn (Builder $proposal) => $proposal->where('status', 'Submitted for Formal Approval')->whereNotNull('proposal_budget_reviewed_at'))
                    ->orWhere(fn (Builder $reports) => $reports->where('status', 'Completion Review')
                        ->whereHas('reports', fn (Builder $report) => $report->where('type', 'Completion')->where('status', 'Submitted')));
            }),
            'treasurer' => $query->where('created_by', '!=', $user->id)->where(function (Builder $projects) use ($user): void {
                $projects->where('status', 'Treasurer Review')
                    ->orWhere(fn (Builder $proposal) => $proposal->where('status', 'Submitted for Formal Approval')
                        ->where('chair_id', '!=', $user->id)->whereNull('proposal_budget_reviewed_at'))
                    ->orWhere(fn (Builder $reports) => $reports->where('status', 'Completion Review')
                        ->where('chair_id', '!=', $user->id)
                        ->whereHas('reports', fn (Builder $report) => $report->where('type', 'Liquidation')->where('status', 'Submitted')));
            }),
            // BOD members can inspect full proposals; only Admin records the signed verdict.
            'bod' => $query->where('status', 'Submitted for Formal Approval')->whereNotNull('proposal_budget_reviewed_at'),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
