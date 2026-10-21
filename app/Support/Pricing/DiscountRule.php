<?php

namespace App\Support\Pricing;

/**
 * A source of discounts (promotions: coupon, buy X get Y · loyalty: member tier).
 * Register implementations in config('roastnotes.discount_rules'), in the order they apply.
 */
interface DiscountRule
{
    /** @return list<DiscountLine> discounts this rule gives the cart (empty = not eligible) */
    public function apply(PricingContext $context): array;
}
