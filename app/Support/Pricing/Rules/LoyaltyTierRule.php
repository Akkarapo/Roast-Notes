<?php

namespace App\Support\Pricing\Rules;

use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\DiscountRule;
use App\Support\Pricing\PricingContext;

/**
 * Member tier % off whatever is left after buy-X-get-Y and the coupon. Shipping is never discounted.
 */
class LoyaltyTierRule implements DiscountRule
{
    public function apply(PricingContext $context): array
    {
        $tier = $context->user->memberTier;
        if (! $tier || $tier->discount_percent <= 0) {
            return [];
        }

        $amount = intdiv($context->remaining() * $tier->discount_percent, 100); // round down

        return $amount > 0 ? [new DiscountLine('loyalty', __(':tier member :percent% off', [
            'tier' => $tier->name,
            'percent' => $tier->discount_percent,
        ]), $amount, ['member_tier_id' => $tier->id])] : [];
    }
}
