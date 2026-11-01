<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Validation\ValidationException;

final class ProjectFinanceGuard
{
    public static function expenseLine(Project $project, string $budgetItemId, float|string $amount, ?int $excludeClaimId = null): array
    {
        $item = collect($project->budget_items ?? [])->firstWhere('id', $budgetItemId);
        if (!$item) self::fail('budget_item_id', 'Choose a specific expense from the approved project budget.');
        if ($project->allocations()->whereNull('budget_item_id')->where('amount', '>', 0)->exists()
            || $project->transactions()->where('status', 'Posted')->where('direction', 'debit')->whereNull('budget_item_id')->exists()) {
            self::fail('budget_item_id', 'Reconcile older allocations and posted expenses with budget lines before adding new spending.');
        }
        $allocation = $project->allocations()->where('budget_item_id', $budgetItemId)->first();
        if (!$allocation) self::fail('budget_item_id', 'The Treasurer must allocate this exact expense line first.');
        $spent = Money::cents($project->transactions()->where('budget_item_id', $budgetItemId)->where('status', 'Posted')->where('direction', 'debit')->sum('amount'));
        $pending = Money::cents($project->expenseClaims()->where('budget_item_id', $budgetItemId)->where('status', 'Pending')->when($excludeClaimId, fn ($query) => $query->where('id', '!=', $excludeClaimId))->sum('amount'));
        if ($spent + $pending + Money::cents($amount) > Money::cents($allocation->amount)) {
            self::fail('amount', 'This expense exceeds the available allocation after posted and pending claims.');
        }
        return $item;
    }

    private static function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
