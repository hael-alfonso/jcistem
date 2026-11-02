<?php

namespace App\Support;

use App\Models\LedgerEntry;

final class ChapterFunds
{
    public static function postedBalance(): string
    {
        $totals = LedgerEntry::query()->where('status', 'Posted')->selectRaw(
            "SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END) AS credits, SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END) AS debits"
        )->first();

        return Money::decimal(Money::cents($totals?->credits) - Money::cents($totals?->debits));
    }
}
