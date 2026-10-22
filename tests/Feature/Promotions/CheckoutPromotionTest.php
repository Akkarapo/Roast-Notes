<?php

namespace Tests\Feature\Promotions;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class CheckoutPromotionTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    private array $shipping = ['recipient_name' => 'สมชาย', 'phone' => '0811111111', 'shipping_address' => 'เชียงใหม่'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
    }

    public function test_order_records_both_promotions_in_order(): void
    {
        $drip = $this->drip(40);
        $deal = Promotion::factory()->buyXGetY(10, 2)->forProducts([$drip])->create(['name' => 'ดริปแบ็ก 10 แถม 2']);
        $coupon = Promotion::factory()->coupon('DRIP10', 10)->forProducts([$drip])->create();
        $this->add($drip, 12); // 480 → −80 free → −40 coupon

        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'DRIP10']);
        $this->actingAs($this->member)->post('/checkout', $this->shipping)->assertSessionHasNoErrors();

        $order = Order::sole();
        $this->assertSame([[$deal->id, 'buy_x_get_y', '80.00'], [$coupon->id, 'coupon', '40.00']],
            $order->discounts->map(fn ($d) => [$d->promotion_id, $d->source, $d->amount])->all());
        $this->assertEquals(480 - 120 + 50, $order->total);
        $this->assertNull(session('coupon_code'));
    }

    public function test_coupon_used_up_meanwhile_blocks_the_order(): void
    {
        $bean = $this->bean(230);
        $promotion = Promotion::factory()->coupon('ONCE', 10, ['per_user_limit' => 1])->forProducts([$bean])->create();
        $this->add($bean, 1);
        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'ONCE']);

        // another order by the same member uses it first
        OrderDiscount::factory()->for(Order::factory()->for($this->member))->create(['promotion_id' => $promotion->id]);

        $this->actingAs($this->member)->post('/checkout', $this->shipping)
            ->assertRedirect('/cart')
            ->assertSessionHasErrors(['coupon' => 'คุณใช้คูปองนี้ครบสิทธิ์แล้ว']);
        $this->assertSame(1, Order::count());
    }

    public function test_cart_shows_hint_when_almost_complete(): void
    {
        $drip = $this->drip(40);
        Promotion::factory()->buyXGetY(10, 2)->forProducts([$drip])->create(['name' => 'ดริปแบ็ก 10 แถม 2']);
        $this->add($drip, 11);

        $this->actingAs($this->member)->get('/cart')->assertSee('ดริปแบ็ก 10 แถม 2 — เพิ่มอีก 1 ชิ้น รับฟรี 2 ชิ้น!');
    }
}
