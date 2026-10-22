<?php

namespace App\Support\Reports;

use App\Support\Money;

/** Headline numbers for the period, each compared with the period before. */
class Summary implements Report
{
    use Queries;

    public function key(): string
    {
        return 'summary';
    }

    public function title(): string
    {
        return __('Period summary');
    }

    public function queries(DateRange $range): array
    {
        return [
            'orders' => $this->soldOrders($range)
                ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(orders.total), 0) AS revenue, COALESCE(SUM(orders.discount_total), 0) AS discounts'),
            'units' => $this->soldLines($range)->selectRaw('COALESCE(SUM(order_items.quantity), 0) AS units'),
        ];
    }

    /** @return array<string, int> revenue/discounts/aov in satang */
    public function numbers(DateRange $range): array
    {
        $q = $this->queries($range);
        $orders = $q['orders']->first();
        $count = (int) $orders->orders;
        $revenue = Money::toSatang($orders->revenue);

        return [
            'revenue' => $revenue,
            'orders' => $count,
            'aov' => $count ? intdiv($revenue, $count) : 0,
            'units' => (int) $q['units']->value('units'),
            'discounts' => Money::toSatang($orders->discounts),
        ];
    }

    public function data(DateRange $range): array
    {
        $now = $this->numbers($range);
        $before = $this->numbers($range->previous());
        $labels = [
            'revenue' => __('Sales'), 'orders' => __('Orders'), 'aov' => __('Average per order'),
            'units' => __('Items sold'), 'discounts' => __('Discounts given'),
        ];
        $money = ['revenue', 'aov', 'discounts'];

        return ['metrics' => collect($labels)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'display' => in_array($key, $money, true) ? Money::bahtFromSatang($now[$key]) : number_format($now[$key]),
            'change' => $before[$key] > 0 ? round(($now[$key] - $before[$key]) / $before[$key] * 100, 1) : null,
            'current' => $now[$key],
            'previous' => $before[$key],
            'money' => in_array($key, $money, true),
        ])->values()->all()];
    }

    public function csv(DateRange $range): array
    {
        $rows = array_map(fn ($m) => [
            $m['label'],
            $m['money'] ? Money::fromSatang($m['current']) : $m['current'],
            $m['money'] ? Money::fromSatang($m['previous']) : $m['previous'],
            $m['change'],
        ], $this->data($range)['metrics']);

        return [[__('Metric'), __('This period'), __('Previous period'), __('Change %')], $rows];
    }
}
