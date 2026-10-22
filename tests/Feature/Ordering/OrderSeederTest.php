<?php

namespace Tests\Feature\Ordering;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Database\Seeders\OrderSeeder;
use Database\Seeders\UserSeeder;
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

    public function test_seeds_sixty_orders_for_member1_to_3_plus_community_customers(): void
    {
        $this->assertTrue(Order::min('placed_at') >= today()->subDays(OrderSeeder::DAYS)->toDateTimeString());
        $this->assertSame(OrderSeeder::ORDERS, Order::whereHas('user', fn ($q) => $q->where('email', 'like', 'member%'))->count());

        $community = array_column(UserSeeder::COMMUNITY, 'email');
        $counts = Order::join('users', 'users.id', '=', 'orders.user_id')->whereIn('email', $community)
            ->selectRaw('email, COUNT(*) AS n')->groupBy('email')->pluck('n', 'email');
        $this->assertCount(count($community), $counts);
        $counts->each(fn ($n) => $this->assertTrue($n >= OrderSeeder::COMMUNITY_ORDERS[0] && $n <= OrderSeeder::COMMUNITY_ORDERS[1]));
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
