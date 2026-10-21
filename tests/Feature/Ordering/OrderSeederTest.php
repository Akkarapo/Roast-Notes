<?php

namespace Tests\Feature\Ordering;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // users + catalog + orders
    }

    public function test_seeds_sixty_orders_over_ninety_days_for_demo_members(): void
    {
        $this->assertSame(OrderSeeder::ORDERS, Order::count());
        $this->assertTrue(Order::min('placed_at') >= today()->subDays(OrderSeeder::DAYS)->toDateTimeString());
        $this->assertSame(
            ['member1@roastnotes.test', 'member2@roastnotes.test', 'member3@roastnotes.test'],
            Order::join('users', 'users.id', '=', 'orders.user_id')->distinct()->orderBy('email')->pluck('email')->all()
        );
    }

    public function test_every_order_adds_up(): void
    {
        Order::with(['items', 'discounts'])->get()->each(function (Order $order) {
            $items = $order->items->sum(fn ($i) => Money::toSatang($i->line_total));
            $this->assertSame($items, Money::toSatang($order->subtotal), $order->order_number);
            $this->assertSame(
                $items - Money::toSatang($order->discount_total) + Money::toSatang($order->shipping_fee),
                Money::toSatang($order->total),
                $order->order_number
            );
        });
    }

    public function test_old_orders_are_delivered_or_cancelled(): void
    {
        $old = Order::where('placed_at', '<', today()->subDays(7))->pluck('status')->map(fn (OrderStatus $s) => $s->value)->unique()->all();

        $this->assertEmpty(array_diff($old, [OrderStatus::Delivered->value, OrderStatus::Cancelled->value]));
    }

    public function test_does_not_touch_stock_and_is_repeatable(): void
    {
        $stockBefore = ProductVariant::orderBy('id')->pluck('stock')->all();
        $snapshot = Order::orderBy('order_number')->get(['order_number', 'total', 'status'])->toArray();

        Order::query()->delete();
        $this->seed(OrderSeeder::class);

        $this->assertSame($stockBefore, ProductVariant::orderBy('id')->pluck('stock')->all());
        $this->assertSame($snapshot, Order::orderBy('order_number')->get(['order_number', 'total', 'status'])->toArray());
    }
}
