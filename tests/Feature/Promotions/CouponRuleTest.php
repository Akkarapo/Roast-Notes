<?php

namespace Tests\Feature\Promotions;

use App\Enums\RoastLevel;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class CouponRuleTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
    }

    public function test_percent_off_only_participating_lines(): void
    {
        $light = $this->bean(230, ['roast_level' => RoastLevel::Light]);
        $dark = $this->bean(300, ['roast_level' => RoastLevel::Dark]);
        Promotion::factory()->coupon('LIGHTROAST15', 15)->forProducts([$light])->create();
        $this->add($light, 2);  // 460
        $this->add($dark, 1);   // 300

        $quote = $this->quote('lightroast15');

        $this->assertSame(6900, $quote->discountTotal); // 15% of 460
        $this->assertSame('LIGHTROAST15 ลด 15%', $quote->discounts[0]->label);
    }

    public function test_rounds_down_to_the_satang(): void
    {
        $bean = $this->bean(333);
        Promotion::factory()->coupon('ODD7', 7)->forProducts([$bean])->create();
        $this->add($bean, 1); // 7% of 333.00 = 23.31

        $this->assertSame(2331, $this->quote('ODD7')->discountTotal);
    }

    public function test_does_not_discount_units_already_free(): void
    {
        $drip = $this->drip(40);
        Promotion::factory()->buyXGetY(10, 2)->forProducts([$drip])->create();
        Promotion::factory()->coupon('DRIP10', 10)->forProducts([$drip])->create();
        $this->add($drip, 12); // 480, 80 free → coupon on 400

        $quote = $this->quote('DRIP10');

        $this->assertSame(['buy_x_get_y', 'coupon'], array_map(fn ($d) => $d->source, $quote->discounts));
        $this->assertSame([8000, 4000], array_map(fn ($d) => $d->amount, $quote->discounts));
    }

    public function test_invalid_coupon_gives_no_discount(): void
    {
        $bean = $this->bean(230);
        $this->add($bean, 1);

        $this->assertSame(0, $this->quote('NOPE')->discountTotal);
    }
}
