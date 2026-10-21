<?php

namespace Tests\Feature\Ordering;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestSellersTest extends TestCase
{
    use RefreshDatabase;

    private function sell(Product $product, int $quantity, OrderStatus $status = OrderStatus::Delivered): void
    {
        OrderItem::factory()
            ->for(Order::factory()->status($status))
            ->create(['product_variant_id' => $product->variants->first()->id, 'quantity' => $quantity]);
    }

    public function test_ranks_products_by_units_sold_across_sizes(): void
    {
        $names = ['A', 'B', 'C', 'D', 'E'];
        $products = collect($names)->mapWithKeys(fn ($n) => [$n => Product::factory()->withVariants()->create(['name' => "Bean $n"])]);
        $this->sell($products['A'], 2);
        $this->sell($products['B'], 9);
        $this->sell($products['C'], 5);
        $this->sell($products['C'], 5);   // 10 total
        $this->sell($products['D'], 1);
        $this->sell($products['E'], 3);

        $best = $this->get('/')->viewData('bestSellers');

        $this->assertSame(['Bean C', 'Bean B', 'Bean E', 'Bean A'], $best->pluck('name')->all());
    }

    public function test_cancelled_orders_and_inactive_products_do_not_count(): void
    {
        $cancelledHit = Product::factory()->withVariants()->create(['name' => 'Cancelled Hit']);
        $hidden = Product::factory()->withVariants()->inactive()->create(['name' => 'Hidden Hit']);
        $real = Product::factory()->withVariants()->create(['name' => 'Real Seller']);
        $this->sell($cancelledHit, 50, OrderStatus::Cancelled);
        $this->sell($hidden, 40);
        $this->sell($real, 1);

        $this->assertSame(['Real Seller'], $this->get('/')->viewData('bestSellers')->pluck('name')->all());
    }

    public function test_section_is_hidden_without_sales(): void
    {
        Product::factory()->withVariants()->create();

        $this->get('/')->assertDontSee('id="best-sellers"', false);
    }
}
