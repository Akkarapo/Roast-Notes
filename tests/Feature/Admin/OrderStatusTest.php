<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'ผู้ดูแลร้าน']);
    }

    private function advance(Order $order, OrderStatus $to, ?string $note = null)
    {
        return $this->actingAs($this->admin)->post("/admin/orders/{$order->order_number}/advance", ['to' => $to->value, 'note' => $note]);
    }

    public function test_list_with_status_tabs_and_search(): void
    {
        Order::factory()->status(OrderStatus::Pending)->create(['order_number' => 'RN-20261020-0001', 'recipient_name' => 'สมชาย']);
        Order::factory()->status(OrderStatus::Delivered)->create(['order_number' => 'RN-20261020-0002', 'recipient_name' => 'สมหญิง']);

        $this->actingAs($this->admin)->get('/admin/orders')->assertOk()->assertSee('RN-20261020-0001')->assertSee('RN-20261020-0002')->assertSee('รอยืนยัน (1)');
        $this->actingAs($this->admin)->get('/admin/orders?status=pending')->assertSee('RN-20261020-0001')->assertDontSee('RN-20261020-0002');
        $this->actingAs($this->admin)->get('/admin/orders?q=สมหญิง')->assertSee('RN-20261020-0002')->assertDontSee('RN-20261020-0001');
    }

    public function test_one_step_at_a_time_up_to_delivered(): void
    {
        $member = User::factory()->create();
        Order::factory()->for($member)->status(OrderStatus::Delivered)->create(['total' => 24900]);
        $order = Order::factory()->for($member)->status(OrderStatus::Pending)->create(['total' => 500]);
        $item = OrderItem::factory()->for($order)->create();

        $this->advance($order, OrderStatus::Shipped)->assertSessionHasErrors('status'); // cannot skip
        $this->advance($order, OrderStatus::Confirmed)->assertSessionHasNoErrors();
        $this->advance($order, OrderStatus::Confirmed)->assertSessionHasErrors('status'); // double click
        $this->advance($order, OrderStatus::Shipped, 'Kerry TH999')->assertSessionHasNoErrors();
        $this->advance($order, OrderStatus::InTransit);
        $this->advance($order, OrderStatus::Delivered)->assertRedirect("/admin/orders/{$order->order_number}");

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(['confirmed', 'shipped', 'in_transit', 'delivered'], $order->statusChanges->pluck('to_status')->map->value->all());
        $this->assertTrue($order->statusChanges->every(fn ($c) => $c->changed_by === $this->admin->id));
        $this->assertSame('barista', $member->fresh()->memberTier->slug); // 24,900 + 500 delivered
        $this->actingAs($member)->get("/orders/{$order->order_number}")->assertSee('data-review-create', false)->assertSee('Kerry TH999');
        $this->actingAs($this->admin)->get("/admin/orders/{$order->order_number}")->assertSee('โดย ผู้ดูแลร้าน')->assertDontSee('data-advance', false);
    }

    public function test_cancel_returns_stock_and_needs_a_reason(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 3]);
        $order = Order::factory()->status(OrderStatus::Confirmed)->create();
        OrderItem::factory()->for($order)->for($variant, 'variant')->create(['quantity' => 2]);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_number}/cancel", [])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_number}/cancel", ['reason' => 'สินค้าเสียหาย'])->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame('สินค้าเสียหาย', $order->statusChanges()->latest('id')->first()->note);
        $this->actingAs($order->user)->get("/orders/{$order->order_number}")->assertSee('สินค้าเสียหาย');
    }

    public function test_cannot_cancel_after_shipping(): void
    {
        $order = Order::factory()->status(OrderStatus::Shipped)->create();

        $this->actingAs($this->admin)->get("/admin/orders/{$order->order_number}")->assertDontSee('data-cancel', false);
        $this->actingAs($this->admin)->post("/admin/orders/{$order->order_number}/cancel", ['reason' => 'x'])->assertSessionHasErrors('order');
        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }
}
