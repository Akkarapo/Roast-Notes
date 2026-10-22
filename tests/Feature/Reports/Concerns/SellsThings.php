<?php

namespace Tests\Feature\Reports\Concerns;

use App\Enums\OrderStatus;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Reports\DateRange;
use Carbon\CarbonImmutable;

trait SellsThings
{
    protected function lot(string $name, RoastLevel $roast, array $notes = []): Product
    {
        $product = Product::factory()->create(['name' => $name, 'roast_level' => $roast]);
        ProductVariant::factory()->for($product)->create(['size' => '250g', 'price' => 300]);
        $product->flavorNotes()->attach(array_map(fn (FlavorNote $n) => $n->id, $notes));

        return $product->load('variants');
    }

    /** one order with one line */
    protected function sell(Product $product, int $units, float $lineTotal, OrderStatus $status = OrderStatus::Delivered, string $at = '2026-10-10 10:00'): OrderItem
    {
        return OrderItem::factory()
            ->for(Order::factory()->status($status)->state(['placed_at' => $at, 'total' => $lineTotal]))
            ->for($product->variants->first(), 'variant')
            ->create(['quantity' => $units, 'line_total' => $lineTotal, 'product_name' => $product->name]);
    }

    /** @return array{0: FlavorCategory, 1: list<FlavorNote>} top-level group with notes under a sub-category */
    protected function group(string $slug, string $name, array $notes): array
    {
        $top = FlavorCategory::factory()->create(['slug' => $slug, 'name' => $name]);
        $sub = FlavorCategory::factory()->create(['slug' => $slug.'-sub', 'name' => $name.' sub', 'parent_id' => $top->id]);

        return [$top, array_map(fn ($n) => FlavorNote::factory()->create(['name' => $n, 'flavor_category_id' => $sub->id]), $notes)];
    }

    protected function october(): DateRange
    {
        return new DateRange(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-23 23:59:59'));
    }
}
