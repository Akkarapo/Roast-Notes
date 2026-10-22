<?php

namespace Tests\Feature\Loyalty;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberInfoTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_info_shows_tier_spend_and_distance(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->status(OrderStatus::Delivered)->create(['total' => 23160]);
        Order::factory()->for($user)->status(OrderStatus::Pending)->create(['total' => 5000]);

        $this->actingAs($user)->get('/member')
            ->assertOk()
            ->assertSee('data-tier-badge="bean"', false)
            ->assertSeeInOrder(['ระดับสมาชิก', 'Bean', 'สะสมยอดซื้อเพื่อปลดล็อกส่วนลดสมาชิก', '฿23,160', 'อีก ฿1,840 ถึงระดับ Barista (ลด 5%)', 'นับเฉพาะออเดอร์ที่ได้รับแล้ว']);
    }

    public function test_top_tier_shows_perks_and_no_next_step(): void
    {
        $user = User::factory()->tier('roaster-master')->create();

        $this->actingAs($user)->get('/member')
            ->assertSee('ลด 10% ทุกออเดอร์ + ซื้อล็อตหายากได้ก่อน 14 วัน')
            ->assertSee('คุณอยู่ระดับสูงสุดแล้ว')
            ->assertDontSee('ถึงระดับ');
    }

    public function test_cart_nudges_towards_the_next_tier(): void
    {
        $user = User::factory()->tier('barista')->create();
        $user->cartOrCreate()->items()->create([
            'product_variant_id' => Product::factory()->withVariants(300)->create()->variants->first()->id,
            'grind' => 'whole_bean', 'quantity' => 1,
        ]);

        $this->actingAs($user)->get('/cart')
            ->assertSee('data-tier-badge="barista"', false)
            ->assertSee('ซื้ออีก ฿50,000 เพื่อเป็น Roaster Master รับส่วนลด 10%');
    }
}
