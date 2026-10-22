<?php

namespace App\Support\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** The two starting points every report shares: sold orders, and sold lines (with their product), in a period. */
trait Queries
{
    /** orders that count as sales: not cancelled, placed in the period */
    protected function soldOrders(DateRange $range): Builder
    {
        return DB::table('orders')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.placed_at', $range->bounds());
    }

    /** order lines of those orders, joined through to the product */
    protected function soldLines(DateRange $range): Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.placed_at', $range->bounds());
    }

    protected static function percent(int|float $part, int|float $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}
