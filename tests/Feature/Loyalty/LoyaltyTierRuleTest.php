<?php

namespace Tests\Feature\Loyalty;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class LoyaltyTierRuleTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    public function test_bean_members_get_no_tier_discount(): void
    {
        $this->member = User::factory()->create();
        $this->add($this->bean(300), 2);

        $this->assertSame([], $this->quote()->discounts);
    }

    public function test_barista_gets_five_percent_rounded_down(): void
    {
        $this->member = User::factory()->tier('barista')->create();
        $this->add($this->bean(333), 1); // 333 × 5% = 16.65

        $quote = $this->quote();
        $this->assertSame(['loyalty', 'สมาชิก Barista ลด 5%', 1665], [$quote->discounts[0]->source, $quote->discounts[0]->label, $quote->discounts[0]->amount]);
        $this->assertSame(33300 - 1665 + 5000, $quote->total); // shipping not discounted
    }

    public function test_tier_discount_comes_last_on_what_is_left(): void
    {
        // Roaster Master: 12 drip bags @ 75 (2 free) + light bean 510 with 15% coupon
        $this->member = User::factory()->tier('roaster-master')->create();
        $drip = $this->drip(75);
        $bean = $this->bean(510);
        Promotion::factory()->buyXGetY(10, 2)->forProducts([$drip])->create();
        Promotion::factory()->coupon('LIGHT15', 15)->forProducts([$bean])->create();
        $this->add($drip, 12);
        $this->add($bean, 1);

        $quote = $this->quote('LIGHT15');

        // 1410 − 150 − 76.50 = 1183.50 → 10% = 118.35
        $this->assertSame(['buy_x_get_y', 'coupon', 'loyalty'], array_map(fn ($d) => $d->source, $quote->discounts));
        $this->assertSame([15000, 7650, 11835], array_map(fn ($d) => $d->amount, $quote->discounts));
        $this->assertSame(141000 - 34485 + 0, $quote->total); // 1065.15 ≥ 1000 → free shipping
    }
}
