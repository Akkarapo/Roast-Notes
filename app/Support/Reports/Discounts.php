<?php

namespace App\Support\Reports;

use App\Support\Money;
use Illuminate\Support\Facades\DB;

/** What each promotion (and the member-tier discount) gave away in the period. */
class Discounts implements Report
{
    use Queries;

    public const SOURCES = ['buy_x_get_y', 'coupon', 'loyalty'];

    public function key(): string
    {
        return 'discounts';
    }

    public function title(): string
    {
        return __('Discounts by promotion');
    }

    public function queries(DateRange $range): array
    {
        return ['discounts' => DB::table('order_discounts')
            ->join('orders', 'orders.id', '=', 'order_discounts.order_id')
            ->leftJoin('promotions', 'promotions.id', '=', 'order_discounts.promotion_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.placed_at', $range->bounds())
            ->groupBy('order_discounts.source', 'order_discounts.promotion_id', 'promotions.name', 'order_discounts.label')
            ->selectRaw('order_discounts.source, order_discounts.promotion_id, promotions.name AS promotion, order_discounts.label, COUNT(DISTINCT order_discounts.order_id) AS orders, SUM(order_discounts.amount) AS amount')
            ->orderByDesc('amount')];
    }

    /** @return list<array{source: string, name: string, label: string, orders: int, amount: int}> */
    public function rows(DateRange $range): array
    {
        return $this->queries($range)['discounts']->get()->map(fn ($r) => [
            'source' => $r->source,
            'name' => $r->promotion ?? $r->label,
            'label' => $r->label,
            'orders' => (int) $r->orders,
            'amount' => Money::toSatang($r->amount),
        ])->all();
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'coupon' => __('Coupon'),
            'buy_x_get_y' => __('Buy X get Y'),
            'loyalty' => __('Member tier'),
            default => $source,
        };
    }

    public function data(DateRange $range): array
    {
        return ['rows' => $this->rows($range)];
    }

    public function csv(DateRange $range): array
    {
        return [[__('Kind'), __('Promotion'), __('Label'), __('Orders'), __('Discount (THB)')], array_map(
            fn ($r) => [self::sourceLabel($r['source']), $r['name'], $r['label'], $r['orders'], Money::fromSatang($r['amount'])], $this->rows($range)
        )];
    }
}
