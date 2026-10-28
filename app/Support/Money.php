<?php

namespace App\Support;

final class Money
{
    public static function cents(string|int|float|null $amount): int
    {
        $value = is_float($amount) ? number_format($amount, 2, '.', '') : (string) ($amount ?? '0');
        if (!preg_match('/^([+-]?)(\d*)(?:\.(\d{0,2}))?$/D', $value, $parts) || !preg_match('/\d/', $value)) throw new \InvalidArgumentException('Invalid decimal amount.');
        return ($parts[1] === '-' ? -1 : 1) * ((int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0'));
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }
}
