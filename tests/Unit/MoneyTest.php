<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_money_keeps_cents_and_accepts_valid_numeric_form_inputs(): void
    {
        foreach (['100.10' => 10010, '.50' => 50, '+10.25' => 1025, '10.' => 1000, '-0.01' => -1, '999999999.99' => 99999999999] as $value => $cents) {
            $this->assertSame($cents, Money::cents($value));
            $this->assertSame($cents, Money::cents(Money::decimal($cents)));
        }
        $this->assertSame(30, Money::cents('0.10') + Money::cents('0.20'));
    }
}
