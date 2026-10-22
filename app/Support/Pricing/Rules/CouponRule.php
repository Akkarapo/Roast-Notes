<?php

namespace App\Support\Pricing\Rules;

use App\Support\Pricing\CartLine;
use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\DiscountRule;
use App\Support\Pricing\PricingContext;
use App\Support\Promotions\CouponValidator;

/**
 * % off the participating lines, after units already made free by buy-X-get-Y. One coupon per order.
 */
class CouponRule implements DiscountRule
{
    public function __construct(private CouponValidator $validator) {}

    public function apply(PricingContext $context): array
    {
        if ($context->couponCode === null) {
            return [];
        }

        $check = $this->validator->check($context->couponCode, $context->user, $context->lines);
        if (! $check->ok()) {
            return []; // the cart page shows $check->reason; PlaceOrder refuses the order
        }

        $coupon = $check->coupon;
        $perLine = [];
        foreach ($this->validator->eligibleLines($coupon, $context->lines) as $line) {
            /** @var CartLine $line */
            $payable = max(0, $line->lineTotal - $context->discountedOnLine($line->item->id));
            $perLine[$line->item->id] = intdiv($payable * $coupon->percent_off, 100); // round down
        }

        $amount = array_sum($perLine);

        return $amount > 0 ? [new DiscountLine('coupon', __(':code :percent% off', ['code' => $coupon->code, 'percent' => $coupon->percent_off]), $amount, [
            'promotion_id' => $coupon->promotion_id,
            'lines' => $perLine,
        ])] : [];
    }
}
