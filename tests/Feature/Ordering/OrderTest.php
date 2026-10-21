<?php

namespace Tests\Feature\Ordering;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Order $order;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $this->variant = ProductVariant::factory()->create(['stock' => 5]);
        $this->order = Order::factory()->for($this->member)->create(['order_number' => 'RN-20261021-0001']);
        OrderItem::factory()->for($this->order)->create(['product_variant_id' => $this->variant->id, 'product_name' => 'Guji Washed', 'quantity' => 3]);
    }

    public function test_owner_sees_the_order(): void
    {
        $this->actingAs($this->member)->get('/orders/RN-20261021-0001')
            ->assertOk()
            ->assertSee('RN-20261021-0001')
            ->assertSee('Guji Washed')
            ->assertSee('รอยืนยัน')
            ->assertSee('ยกเลิกคำสั่งซื้อ');
    }

    public function test_other_members_get_404(): void
    {
        $this->get('/orders/RN-20261021-0001')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/orders/RN-20261021-0001')->assertNotFound();
        $this->actingAs(User::factory()->create())->post('/orders/RN-20261021-0001/cancel')->assertNotFound();
    }

    public function test_member_cancels_a_pending_order_and_stock_comes_back(): void
    {
        $this->actingAs($this->member)->post('/orders/RN-20261021-0001/cancel')->assertRedirect('/orders/RN-20261021-0001');

        $this->order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $this->order->status);
        $this->assertNotNull($this->order->cancelled_at);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    public function test_orders_past_pending_cannot_be_cancelled_by_member(): void
    {
        $this->order->update(['status' => OrderStatus::Confirmed]);

        $this->actingAs($this->member)->from('/orders/RN-20261021-0001')->post('/orders/RN-20261021-0001/cancel')->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Confirmed, $this->order->fresh()->status);
        $this->assertSame(5, $this->variant->fresh()->stock);
        $this->actingAs($this->member)->get('/orders/RN-20261021-0001')->assertDontSee('ยกเลิกคำสั่งซื้อ');
    }

    public function test_cancelling_twice_does_not_return_stock_twice(): void
    {
        $this->actingAs($this->member)->post('/orders/RN-20261021-0001/cancel');
        $this->actingAs($this->member)->post('/orders/RN-20261021-0001/cancel')->assertSessionHasErrors('order');

        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    public function test_member_page_lists_latest_orders_first(): void
    {
        Order::factory()->for($this->member)->create(['order_number' => 'RN-20261020-0001', 'placed_at' => now()->subDay()]);
        Order::factory()->for($this->member)->create(['order_number' => 'RN-20261022-0001', 'placed_at' => now()->addDay()]);
        Order::factory()->create(['order_number' => 'RN-20261021-0099']); // someone else

        $this->actingAs($this->member)->get('/member')
            ->assertSeeInOrder(['ประวัติการสั่งซื้อ', 'RN-20261022-0001', 'RN-20261021-0001', 'RN-20261020-0001'])
            ->assertDontSee('RN-20261021-0099');
    }

    public function test_orders_index_paginates_own_orders(): void
    {
        $this->actingAs($this->member)->get('/orders')->assertOk()->assertSee('RN-20261021-0001');
    }
}
