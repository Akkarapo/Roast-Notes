<?php

namespace Tests\Feature\Promotions;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class CartCouponTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $bean = $this->bean(230);
        Promotion::factory()->coupon('LIGHTROAST15', 15)->forProducts([$bean])->create();
        Promotion::factory()->coupon('OTHER10', 10)->forProducts([$bean])->create();
        Promotion::factory()->coupon('SUMMER20', 20)->expired()->forProducts([$bean])->create();
        $this->add($bean, 2); // ฿460
    }

    public function test_apply_and_see_discount_on_cart_and_checkout(): void
    {
        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'lightroast15'])
            ->assertRedirect('/cart')->assertSessionHas('coupon_code', 'LIGHTROAST15');

        $this->actingAs($this->member)->get('/cart')
            ->assertSee('data-coupon-applied', false)
            ->assertSee('LIGHTROAST15 ลด 15%')
            ->assertSee('−฿69');

        $this->actingAs($this->member)->get('/checkout')
            ->assertSee('ใช้คูปอง LIGHTROAST15')
            ->assertSee('−฿69')
            ->assertDontSee('name="code"', false); // checkout only shows the coupon
    }

    public function test_invalid_code_shows_the_reason(): void
    {
        $this->actingAs($this->member)->from('/cart')->post('/cart/coupon', ['code' => 'SUMMER20'])
            ->assertSessionHasErrors(['coupon' => 'คูปองนี้หมดอายุแล้ว'])
            ->assertSessionMissing('coupon_code');
    }

    public function test_second_coupon_replaces_the_first_and_can_be_removed(): void
    {
        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'LIGHTROAST15']);
        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'OTHER10'])->assertSessionHas('coupon_code', 'OTHER10');

        $this->actingAs($this->member)->delete('/cart/coupon')->assertSessionMissing('coupon_code');
    }

    public function test_coupon_that_stops_working_is_flagged_in_cart_and_blocks_checkout_page(): void
    {
        $this->actingAs($this->member)->post('/cart/coupon', ['code' => 'LIGHTROAST15']);
        Promotion::whereHas('coupon', fn ($q) => $q->where('code', 'LIGHTROAST15'))->update(['ends_at' => now()->subMinute()]);

        $this->actingAs($this->member)->get('/cart')->assertSee('คูปองนี้หมดอายุแล้ว');
        $this->actingAs($this->member)->get('/checkout')->assertRedirect('/cart');
    }
}
