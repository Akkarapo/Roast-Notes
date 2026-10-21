<?php

namespace Tests\Feature\Ordering;

use App\Enums\Grind;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_cart_per_user(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($user->cartOrCreate()->is($user->cartOrCreate()));

        $this->expectException(QueryException::class);
        Cart::factory()->for($user)->create();
    }

    public function test_same_variant_and_grind_is_one_line(): void
    {
        $cart = Cart::factory()->create();
        $variant = ProductVariant::factory()->create();
        CartItem::factory()->for($cart)->create(['product_variant_id' => $variant->id, 'grind' => Grind::Drip]);
        CartItem::factory()->for($cart)->create(['product_variant_id' => $variant->id, 'grind' => Grind::Espresso]);

        $this->expectException(QueryException::class);
        CartItem::factory()->for($cart)->create(['product_variant_id' => $variant->id, 'grind' => Grind::Drip]);
    }

    public function test_order_number_is_unique(): void
    {
        Order::factory()->create(['order_number' => 'RN-20261021-0001']);

        $this->expectException(QueryException::class);
        Order::factory()->create(['order_number' => 'RN-20261021-0001']);
    }

    public function test_a_variant_that_was_ordered_cannot_be_deleted(): void
    {
        $item = OrderItem::factory()->create();

        $this->expectException(QueryException::class);
        $item->variant->delete();
    }

    public function test_order_relations(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->for($order)->create();
        OrderDiscount::factory()->for($order)->create();

        $this->assertCount(2, $order->items);
        $this->assertCount(1, $order->discounts);
        $this->assertTrue($order->user->orders->first()->is($order));
    }
}
