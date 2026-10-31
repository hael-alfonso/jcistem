<?php

namespace App\Support;

final class ProjectBudget
{
    public const CATEGORIES = [
        'venue_logistics' => 'Venue and logistics',
        'materials_supplies' => 'Materials and supplies',
        'marketing_communications' => 'Marketing and communications',
        'food_refreshments' => 'Food and refreshments',
        'transportation' => 'Transportation',
        'professional_services' => 'Professional services',
        'documentation' => 'Documentation',
        'contingency' => 'Contingency',
        'other' => 'Other expense',
    ];

    public static function normalize(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && collect($item)->contains(fn ($value) => $value !== null && $value !== ''))
            ->map(function (array $item) {
                $quantity = number_format((float) ($item['quantity'] ?? 0), 2, '.', '');
                $unitCost = Money::cents($item['unit_cost'] ?? 0);
                $lineTotal = (int) round((float) $quantity * $unitCost);

                return [
                    'category' => (string) ($item['category'] ?? ''),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'quantity' => $quantity,
                    'unit_cost' => Money::decimal($unitCost),
                    'amount' => Money::decimal($lineTotal),
                ];
            })
            ->values()
            ->all();
    }

    public static function total(array $items): string
    {
        return Money::decimal(array_sum(array_map(fn ($item) => Money::cents($item['amount'] ?? 0), $items)));
    }

    public static function officialExpenseLines(array $items): array
    {
        $totals = array_fill_keys(['logistics_venue', 'marketing_materials', 'tokens_refreshments', 'other_expenses'], 0);
        foreach ($items as $item) {
            $officialCategory = match ($item['category'] ?? 'other') {
                'venue_logistics', 'transportation', 'professional_services' => 'logistics_venue',
                'materials_supplies', 'marketing_communications', 'documentation' => 'marketing_materials',
                'food_refreshments' => 'tokens_refreshments',
                default => 'other_expenses',
            };
            $totals[$officialCategory] += Money::cents($item['amount'] ?? 0);
        }

        return array_map(Money::decimal(...), $totals);
    }
}
