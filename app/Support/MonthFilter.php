<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class MonthFilter
{
    public static function start(string $month): string
    {
        return $month.'-01';
    }

    public static function end(string $month): string
    {
        return CarbonImmutable::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();
    }
}
