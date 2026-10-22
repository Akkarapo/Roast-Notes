<?php

namespace Tests\Feature\Promotions;

use App\Models\Promotion;
use App\Models\User;
use App\Support\Pricing\Rules\BuyXGetYRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class BuyXGetYRuleTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
    }

    private function deal(array $products, ?callable $state = null): Promotion
    {
        $factory = Promotion::factory()->buyXGetY(10, 2)->forProducts($products);

        return ($state ? $state($factory) : $factory)->create(['name' => 'ดริปแบ็ก 10 แถม 2']);
    }

    public function test_ten_units_get_nothing_twelve_get_two_free(): void
    {
        $drip = $this->drip(40);
        $this->deal([$drip]);

        $this->add($drip, 10);
        $this->assertSame(0, $this->quote()->discountTotal);

        $this->member->cart->items()->update(['quantity' => 12]);
        $quote = $this->quote();
        $this->assertSame(8000, $quote->discountTotal);   // 2 × ฿40
        $this->assertSame('ดริปแบ็ก 10 แถม 2', $quote->discounts[0]->label);
    }

    public function test_repeats_for_every_full_group(): void
    {
        $drip = $this->drip(40);
        $this->deal([$drip]);
        $this->add($drip, 25); // 2 full groups of 12

        $this->assertSame(4 * 4000, $this->quote()->discountTotal);
    }

    public function test_cheapest_units_are_free_across_products(): void
    {
        [$cheap, $dear] = [$this->drip(30), $this->drip(50)];
        $this->deal([$cheap, $dear]);
        $this->add($dear, 10);
        $this->add($cheap, 2);

        $quote = $this->quote();
        $this->assertSame(6000, $quote->discountTotal); // the two ฿30 sachets
        $this->assertSame([$this->member->cart->items->last()->id => 6000], $quote->discounts[0]->meta['lines']);
    }

    public function test_only_participating_products_count(): void
    {
        [$in, $out] = [$this->drip(40), $this->drip(40)];
        $this->deal([$in]);
        $this->add($in, 10);
        $this->add($out, 5);

        $this->assertSame(0, $this->quote()->discountTotal);
    }

    public function test_expired_or_switched_off_deals_do_nothing(): void
    {
        $drip = $this->drip(40);
        $this->deal([$drip], fn ($f) => $f->expired());
        $this->deal([$drip], fn ($f) => $f->state(['is_active' => false]));
        $this->add($drip, 12);

        $this->assertSame(0, $this->quote()->discountTotal);
    }

    public function test_hint_when_almost_complete(): void
    {
        $drip = $this->drip(40);
        $this->deal([$drip]);
        $this->add($drip, 10);

        $hints = app(BuyXGetYRule::class)->hints($this->quote()->lines);
        $this->assertSame([['promotion' => 'ดริปแบ็ก 10 แถม 2', 'add' => 2, 'free' => 2]], $hints);

        $this->member->cart->items()->update(['quantity' => 12]);
        $this->assertSame([], app(BuyXGetYRule::class)->hints($this->quote()->lines));
    }
}
