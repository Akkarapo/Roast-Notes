<?php

namespace Tests\Feature\Loyalty;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyAccessTest extends TestCase
{
    use RefreshDatabase;

    private Product $rare;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rare = Product::factory()->withVariants(600)->exclusive(now()->addDays(14))->create(['slug' => 'rare-lot']);
    }

    private function addToCart(User $user)
    {
        return $this->actingAs($user)->post('/cart/items', ['variant_id' => $this->rare->variants->first()->id, 'quantity' => 1, 'grind' => 'whole_bean']);
    }

    public function test_roaster_master_can_buy_a_rare_lot(): void
    {
        $master = User::factory()->tier('roaster-master')->create();

        $this->actingAs($master)->get('/products/rare-lot')->assertSee('คุณซื้อก่อนได้')->assertDontSee('data-early-access-locked', false);
        $this->addToCart($master)->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($master)->post('/checkout', ['recipient_name' => 'ก', 'phone' => '0800000000', 'shipping_address' => 'ข'])->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());
    }

    public function test_other_tiers_see_the_date_but_cannot_add(): void
    {
        $barista = User::factory()->tier('barista')->create();
        $date = now()->addDays(14)->translatedFormat('j M Y');

        $this->actingAs($barista)->get('/products/rare-lot')
            ->assertSee('Roaster Master ซื้อก่อน · เปิดขายทั่วไป '.$date)
            ->assertSee('ล็อตนี้ซื้อได้เฉพาะสมาชิก Roaster Master จนถึง '.$date);
        $this->addToCart($barista)->assertForbidden();
        $this->assertSame(0, $barista->cartOrCreate()->items()->count());
    }

    public function test_guests_see_the_lock_too(): void
    {
        $this->get('/products/rare-lot')->assertSee('data-early-access-locked', false);
        $this->get('/products')->assertSee('Roaster Master ซื้อก่อน');
    }

    public function test_place_order_refuses_a_rare_lot_already_in_the_cart(): void
    {
        $bean = User::factory()->create();
        $bean->cartOrCreate()->items()->create(['product_variant_id' => $this->rare->variants->first()->id, 'grind' => 'whole_bean', 'quantity' => 1]);

        $this->actingAs($bean)->post('/checkout', ['recipient_name' => 'ก', 'phone' => '0800000000', 'shipping_address' => 'ข'])
            ->assertSessionHasErrors(['cart' => 'ล็อตนี้ซื้อได้เฉพาะสมาชิก Roaster Master จนถึง '.now()->addDays(14)->translatedFormat('j M Y')]);
        $this->assertSame(0, Order::count());
    }

    public function test_everyone_can_buy_after_the_window(): void
    {
        $this->travelTo(now()->addDays(15));
        $bean = User::factory()->create();

        $this->get('/products/rare-lot')->assertDontSee('data-early-access', false);
        $this->addToCart($bean)->assertSessionHasNoErrors();
        $this->assertSame(1, $bean->cartOrCreate()->items()->count());
    }

    public function test_rare_filter(): void
    {
        Product::factory()->withVariants()->create(['name' => 'Ordinary Lot']);

        $this->get('/products?rare=1')->assertSee($this->rare->name)->assertDontSee('Ordinary Lot');
    }
}
