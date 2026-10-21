<?php

namespace App\Support;

class Money
{
    /** 1090.00 → "฿1,090", 35.50 → "฿35.50" */
    public static function baht(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return '฿'.number_format($amount, floor($amount) == $amount ? 0 : 2);
    }
}
