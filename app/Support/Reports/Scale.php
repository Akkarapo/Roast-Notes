<?php

namespace App\Support\Reports;

/** Clean axis ticks for the SVG charts: 0 / 5K / 10K / 15K ... */
final class Scale
{
    /** @return list<float> ticks from 0 to a "nice" max (inclusive) */
    public static function ticks(float $max, int $count = 4): array
    {
        if ($max <= 0) {
            return [0, 1];
        }
        $raw = $max / $count;
        $magnitude = 10 ** floor(log10($raw));
        $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $magnitude)->first(fn ($s) => $s >= $raw);
        $top = ceil($max / $step) * $step;

        return array_map(fn ($i) => $i * $step, range(0, (int) round($top / $step)));
    }

    /** 1,200 → 1.2K, 47,090 → 47K, 1,500,000 → 1.5M */
    public static function compact(float $value): string
    {
        return match (true) {
            $value >= 1_000_000 => rtrim(rtrim(number_format($value / 1_000_000, 1), '0'), '.').'M',
            $value >= 1_000 => rtrim(rtrim(number_format($value / 1_000, 1), '0'), '.').'K',
            default => rtrim(rtrim(number_format($value, 1), '0'), '.'),
        };
    }
}
