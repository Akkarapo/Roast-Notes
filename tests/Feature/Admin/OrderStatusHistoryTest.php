<?php

namespace Tests\Feature\Admin;

use App\Actions\CancelOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusChange;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    private array $shipping = ['recipient_name' => 'ก', 'phone' => '0800000000', 'shipping_address' => 'ข'];

    public function test_placing_and_cancelling_are_recorded_by_the_member(): void
    {
        $member = User::factory()->create();
        $member->cartOrCreate()->items()->create(['product_variant_id' => Product::factory()->withVariants()->create()->variants->first()->id, 'grind' => 'whole_bean', 'quantity' => 1]);

        $this->actingAs($member)->post('/checkout', $this->shipping);
        $order = Order::sole();
        $this->actingAs($member)->post("/orders/{$order->order_number}/cancel");

        $this->assertSame(
            [[null, 'pending', $member->id], ['pending', 'cancelled', $member->id]],
            $order->statusChanges->map(fn (OrderStatusChange $c) => [$c->from_status?->value, $c->to_status->value, $c->changed_by])->all()
        );
    }

    public function test_admin_cancel_records_admin_and_note(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->status(OrderStatus::Confirmed)->create();

        app(CancelOrder::class)($order, byMember: false, by: $admin, note: 'ลูกค้าโทรมายกเลิก');

        $change = $order->statusChanges->last();
        $this->assertSame([OrderStatus::Confirmed, OrderStatus::Cancelled, $admin->id, 'ลูกค้าโทรมายกเลิก'], [$change->from_status, $change->to_status, $change->changed_by, $change->note]);
    }

    public function test_member_sees_timeline_without_admin_names(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'ผู้ดูแลร้าน']);
        $order = Order::factory()->status(OrderStatus::InTransit)->create();
        $order->recordStatus(null, OrderStatus::Pending, $order->user);
        $order->recordStatus(OrderStatus::Pending, OrderStatus::Confirmed, $admin);
        $order->recordStatus(OrderStatus::Confirmed, OrderStatus::Shipped, $admin, 'Kerry TH123');

        $this->actingAs($order->user)->get("/orders/{$order->order_number}")
            ->assertSee('data-order-timeline', false)
            ->assertSeeInOrder(['สั่งซื้อแล้ว', 'ยืนยันแล้ว', 'ส่งพัสดุแล้ว', 'Kerry TH123'])
            ->assertDontSee('โดย ผู้ดูแลร้าน');
    }

    public function test_seeded_orders_have_a_consistent_history(): void
    {
        $this->seed();

        Order::with('statusChanges')->get()->each(function (Order $order) {
            $changes = $order->statusChanges;
            $this->assertNull($changes->first()->from_status, $order->order_number);
            $this->assertSame($order->status, $changes->last()->to_status, $order->order_number);
            $changes->sliding(2)->each(fn ($pair) => $this->assertSame($pair->first()->to_status, $pair->last()->from_status));
            $this->assertTrue($changes->every(fn ($c) => $c->created_at->lte(now())));
        });
    }
}
