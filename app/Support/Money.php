<?php

namespace App\Support;

/**
 * Money is stored as DECIMAL(10,2) and computed as integer satang (1 baht = 100 satang)
 * so no float rounding ever touches a price.
 */
class Money
{
    /** "1090.50" / 1090.5 / 1090 → 109050 */
    public static function toSatang(float|int|string|null $amount): int
    {
        $text = number_format((float) ($amount ?? 0), 2, '.', '');
        [$baht, $satang] = explode('.', ltrim($text, '-'));
        $value = (int) $baht * 100 + (int) $satang;

        return str_starts_with($text, '-') ? -$value : $value;
    }

    /** 109050 → "1090.50" (for DECIMAL columns) */
    public static function fromSatang(int $satang): string
    {
        $sign = $satang < 0 ? '-' : '';
        $satang = abs($satang);

        return $sign.intdiv($satang, 100).'.'.str_pad((string) ($satang % 100), 2, '0', STR_PAD_LEFT);
    }

    /** 1090.00 → "฿1,090", 35.50 → "฿35.50" */
    public static function baht(float|int|string|null $amount): string
    {
        $satang = self::toSatang($amount);

        return ($satang < 0 ? '-' : '').'฿'.number_format(abs($satang) / 100, $satang % 100 === 0 ? 0 : 2);
    }

    /** Same as baht() but takes satang. */
    public static function bahtFromSatang(int $satang): string
    {
        return self::baht(self::fromSatang($satang));
    }
}
