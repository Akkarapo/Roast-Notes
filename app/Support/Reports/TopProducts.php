<?php

namespace App\Support\Reports;

use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Support\Money;

/** Ten best sellers by units (revenue breaks ties), beans and drip bags together. */
class TopProducts implements Report
{
    use Queries;

    public const LIMIT = 10;

    public function key(): string
    {
        return 'top-products';
    }

    public function title(): string
    {
        return __('Best sellers');
    }

    public function queries(DateRange $range): array
    {
        return ['top' => $this->soldLines($range)
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.type', 'products.roast_level')
            ->selectRaw('products.id, products.name, products.slug, products.type, products.roast_level, SUM(order_items.quantity) AS units, SUM(order_items.line_total) AS revenue')
            ->orderByDesc('units')->orderByDesc('revenue')->orderBy('products.name')
            ->limit(self::LIMIT)];
    }

    /** @return list<array{name: string, slug: string, type: ProductType, roast: RoastLevel, units: int, revenue: int}> */
    public function rows(DateRange $range): array
    {
        return $this->queries($range)['top']->get()->map(fn ($r) => [
            'name' => $r->name, 'slug' => $r->slug, 'type' => ProductType::from($r->type), 'roast' => RoastLevel::from($r->roast_level),
            'units' => (int) $r->units, 'revenue' => Money::toSatang($r->revenue),
        ])->all();
    }

    public function data(DateRange $range): array
    {
        return ['rows' => $this->rows($range)];
    }

    public function csv(DateRange $range): array
    {
        $rows = [];
        foreach ($this->rows($range) as $i => $r) {
            $rows[] = [$i + 1, $r['name'], $r['type']->label(), $r['roast']->label(), $r['units'], Money::fromSatang($r['revenue'])];
        }

        return [[__('Rank'), __('Product'), __('Type'), __('Roast'), __('Units'), __('Sales (THB)')], $rows];
    }
}
