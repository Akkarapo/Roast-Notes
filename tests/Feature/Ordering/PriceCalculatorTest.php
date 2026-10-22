<?php

namespace Tests\Feature\Ordering;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\DiscountRule;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\PricingContext;
use App\Support\Pricing\Rules\BuyXGetYRule;
use App\Support\Pricing\Rules\CouponRule;
use App\Support\Pricing\Rules\LoyaltyTierRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function cartWith(array $lines): Cart
    {
        $cart = Cart::factory()->create();
        foreach ($lines as [$price, $quantity]) {
            CartItem::factory()->for($cart)->create([
                'product_variant_id' => ProductVariant::factory()->create(['price' => $price])->id,
                'quantity' => $quantity,
            ]);
        }

        return $cart;
    }

    /** A rule that discounts a fixed amount and records what it saw. */
    private function rule(string $source, int $amount, array &$seen = []): DiscountRule
    {
        return new class($source, $amount, $seen) implements DiscountRule
        {
            public function __construct(private string $source, private int $amount, private array &$seen) {}

            public function apply(PricingContext $context): array
            {
                $this->seen[] = [$this->source, $context->discountedSoFar, $context->remaining()];

                return [new DiscountLine($this->source, "$this->source label", $this->amount)];
            }
        };
    }

    public function test_subtotal_and_shipping_below_threshold(): void
    {
        $quote = (new PriceCalculator([]))->quote($this->cartWith([['230.00', 2], ['35.50', 1]]));

        $this->assertSame(49550, $quote->subtotal);   // 460 + 35.50
        $this->assertSame(5000, $quote->shippingFee);
        $this->assertSame(54550, $quote->total);
        $this->assertSame(3, $quote->itemCount());
        $this->assertSame(50450, $quote->freeShippingGap());
    }

    public function test_free_shipping_at_one_thousand_after_discounts(): void
    {
        $cart = $this->cartWith([['500.00', 2]]);

        $this->assertSame(0, (new PriceCalculator([]))->quote($cart)->shippingFee);
        $this->assertNull((new PriceCalculator([]))->quote($cart)->freeShippingGap());

        $discounted = (new PriceCalculator([$this->rule('coupon', 100)]))->quote($cart); // 1000 - 1 baht
        $this->assertSame(5000, $discounted->shippingFee);
        $this->assertSame(100000 - 100 + 5000, $discounted->total);
    }

    public function test_rules_run_in_order_and_see_earlier_discounts(): void
    {
        $seen = [];
        $calculator = new PriceCalculator([$this->rule('buy_x_get_y', 10000, $seen), $this->rule('coupon', 5000, $seen), $this->rule('tier', 2000, $seen)]);

        $quote = $calculator->quote($this->cartWith([['300.00', 1]]));

        $this->assertSame([['buy_x_get_y', 0, 30000], ['coupon', 10000, 20000], ['tier', 15000, 15000]], $seen);
        $this->assertSame(['buy_x_get_y', 'coupon', 'tier'], array_map(fn ($d) => $d->source, $quote->discounts));
        $this->assertSame(17000, $quote->discountTotal);
    }

    public function test_discounts_never_exceed_subtotal(): void
    {
        $quote = (new PriceCalculator([$this->rule('coupon', 25000), $this->rule('tier', 25000), $this->rule('extra', 100)]))
            ->quote($this->cartWith([['300.00', 1]]));

        $this->assertSame([25000, 5000], array_map(fn ($d) => $d->amount, $quote->discounts));
        $this->assertSame(30000, $quote->discountTotal);
        $this->assertSame(5000, $quote->total); // only shipping left
    }

    public function test_empty_cart_costs_nothing(): void
    {
        $quote = (new PriceCalculator([]))->quote(Cart::factory()->create());

        $this->assertTrue($quote->isEmpty());
        $this->assertSame(0, $quote->total);
    }

    public function test_rules_come_from_config_by_default(): void
    {
        $this->assertSame([
            BuyXGetYRule::class,
            CouponRule::class,
            LoyaltyTierRule::class,
        ], config('roastnotes.discount_rules'));
        $this->assertSame(0, (new PriceCalculator)->quote($this->cartWith([['100.00', 1]]))->discountTotal); // no promotions exist
    }
}
