<?php

namespace App\Support\Reports;

use App\Support\Money;

/** Sales per day (short periods) or per month, with empty days/months shown as 0. */
class SalesOverTime implements Report
{
    use Queries;

    public function key(): string
    {
        return 'sales-over-time';
    }

    public function title(): string
    {
        return __('Sales over time');
    }

    public function queries(DateRange $range): array
    {
        $period = $range->granularity() === 'day' ? "DATE_FORMAT(orders.placed_at, '%Y-%m-%d')" : "DATE_FORMAT(orders.placed_at, '%Y-%m')";

        return ['sales' => $this->soldOrders($range)
            ->selectRaw("{$period} AS period, COUNT(*) AS orders, SUM(orders.total) AS revenue")
            ->groupByRaw($period)
            ->orderBy('period')];
    }

    /** @return list<array{period: string, label: string, title: string, orders: int, revenue: int}> */
    public function rows(DateRange $range): array
    {
        $found = $this->queries($range)['sales']->get()->keyBy('period');
        $daily = $range->granularity() === 'day';
        $cursor = $daily ? $range->start->startOfDay() : $range->start->startOfMonth();
        $rows = [];

        while ($cursor->lte($range->end)) {
            $key = $cursor->format($daily ? 'Y-m-d' : 'Y-m');
            $row = $found->get($key);
            $rows[] = [
                'period' => $key,
                'label' => $daily ? $cursor->translatedFormat('j M') : $cursor->translatedFormat('M y'),
                'title' => $daily ? $cursor->translatedFormat('j F Y') : $cursor->translatedFormat('F Y'),
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => Money::toSatang($row->revenue ?? 0),
            ];
            $cursor = $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        return $rows;
    }

    public function data(DateRange $range): array
    {
        $rows = $this->rows($range);

        return [
            'rows' => $rows,
            'granularity' => $range->granularity(),
            'bars' => array_map(fn ($r) => [
                'label' => $r['label'], 'title' => $r['title'], 'value' => $r['revenue'] / 100, 'display' => Money::bahtFromSatang($r['revenue']),
            ], $rows),
        ];
    }

    public function csv(DateRange $range): array
    {
        return [[__('Period'), __('Orders'), __('Sales (THB)')], array_map(
            fn ($r) => [$r['period'], $r['orders'], Money::fromSatang($r['revenue'])], $this->rows($range)
        )];
    }
}
