<?php

namespace Tests\Feature\Loyalty;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class CheckoutLoyaltyTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    private array $shipping = ['recipient_name' => 'สมหญิง', 'phone' => '0822222222', 'shipping_address' => 'กรุงเทพฯ'];

    public function test_tier_discount_is_the_same_in_cart_checkout_and_order(): void
    {
        $this->member = User::factory()->tier('barista')->create();
        $this->add($this->bean(400), 2); // 800 − 40 = 760 + 50 shipping

        $this->actingAs($this->member)->get('/cart')->assertSee('สมาชิก Barista ลด 5%')->assertSee('−฿40');
        $this->actingAs($this->member)->get('/checkout')->assertSee('สมาชิก Barista ลด 5%')->assertSee('฿810');
        $this->actingAs($this->member)->post('/checkout', $this->shipping)->assertSessionHasNoErrors();

        $order = Order::sole();
        $this->assertSame([[null, 'loyalty', '40.00']], $order->discounts->map(fn ($d) => [$d->promotion_id, $d->source, $d->amount])->all());
        $this->assertSame('810.00', $order->total);
        $this->actingAs($this->member)->get('/orders/'.$order->order_number)->assertSee('สมาชิก Barista ลด 5%');
    }

    public function test_free_shipping_is_decided_after_the_tier_discount(): void
    {
        $this->member = User::factory()->tier('roaster-master')->create();
        $this->add($this->bean(1050), 1); // 1050 − 105 = 945 < 1000 → pay shipping

        $this->actingAs($this->member)->post('/checkout', $this->shipping);

        $this->assertSame(['105.00', '50.00', '995.00'], [Order::sole()->discount_total, Order::sole()->shipping_fee, Order::sole()->total]);
    }
}
