<?php

namespace App\Support\Reports;

use App\Enums\RoastLevel;
use App\Support\Money;

/** Units and sales split by roast level. */
class RoastShare implements Report
{
    use Queries;

    public function key(): string
    {
        return 'roast-share';
    }

    public function title(): string
    {
        return __('Share by roast level');
    }

    public function queries(DateRange $range): array
    {
        return ['roast' => $this->soldLines($range)
            ->groupBy('products.roast_level')
            ->selectRaw('products.roast_level, SUM(order_items.quantity) AS units, SUM(order_items.line_total) AS revenue')];
    }

    /** @return list<array{roast: RoastLevel, units: int, revenue: int, share: float}> Light → Dark, missing levels as 0 */
    public function rows(DateRange $range): array
    {
        $found = $this->queries($range)['roast']->get()->keyBy('roast_level');
        $total = (int) $found->sum('units');

        return array_map(function (RoastLevel $level) use ($found, $total) {
            $units = (int) ($found[$level->value]->units ?? 0);

            return ['roast' => $level, 'units' => $units, 'revenue' => Money::toSatang($found[$level->value]->revenue ?? 0), 'share' => self::percent($units, $total)];
        }, RoastLevel::cases());
    }

    public function data(DateRange $range): array
    {
        return ['rows' => $this->rows($range)];
    }

    public function csv(DateRange $range): array
    {
        return [[__('Roast'), __('Units'), __('Share of units %'), __('Sales (THB)')], array_map(
            fn ($r) => [$r['roast']->label(), $r['units'], $r['share'], Money::fromSatang($r['revenue'])], $this->rows($range)
        )];
    }
}
