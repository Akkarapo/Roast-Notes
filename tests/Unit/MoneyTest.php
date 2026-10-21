<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_satang_round_trip(): void
    {
        $this->assertSame(109050, Money::toSatang('1090.50'));
        $this->assertSame(109000, Money::toSatang(1090));
        $this->assertSame(1, Money::toSatang('0.01'));
        $this->assertSame(-5000, Money::toSatang('-50.00'));
        $this->assertSame('1090.50', Money::fromSatang(109050));
        $this->assertSame('0.05', Money::fromSatang(5));
        $this->assertSame('-50.00', Money::fromSatang(-5000));
    }

    public function test_baht_formatting(): void
    {
        $this->assertSame('฿1,090', Money::baht('1090.00'));
        $this->assertSame('฿35.50', Money::baht(35.5));
        $this->assertSame('฿0', Money::bahtFromSatang(0));
        $this->assertSame('-฿50', Money::bahtFromSatang(-5000));
    }
}
