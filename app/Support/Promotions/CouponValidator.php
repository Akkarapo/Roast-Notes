<?php

namespace App\Support\Promotions;

use App\Models\Coupon;
use App\Models\Promotion;
use App\Models\User;
use App\Support\Money;
use App\Support\Pricing\CartLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decides whether a coupon code can be used on a cart, and why not.
 * Used when the code is entered, when the cart is shown and again inside PlaceOrder.
 */
class CouponValidator
{
    /** @param Collection<int, CartLine> $lines */
    public function check(string $code, User $user, Collection $lines): CouponCheck
    {
        $coupon = Coupon::with('promotion.products:id')->where('code', Coupon::normalize($code))->first();
        if (! $coupon || ! $coupon->promotion->is_active) {
            return CouponCheck::fail(__('Coupon not found.'));
        }

        $promotion = $coupon->promotion;
        if (DB::transactionLevel() > 0) {
            // placing an order: lock the promotion row so two orders cannot both take the last use
            Promotion::whereKey($promotion->id)->lockForUpdate()->first();
        }

        if ($promotion->starts_at->isFuture()) {
            return CouponCheck::fail(__('This coupon is not active yet.'), $coupon);
        }
        if ($promotion->ends_at?->isPast()) {
            return CouponCheck::fail(__('This coupon has expired.'), $coupon);
        }
        if ($coupon->usage_limit !== null && $promotion->timesUsed() >= $coupon->usage_limit) {
            return CouponCheck::fail(__('This coupon has been fully redeemed.'), $coupon);
        }
        if ($coupon->per_user_limit !== null && $promotion->timesUsed($user) >= $coupon->per_user_limit) {
            return CouponCheck::fail(__('You have already used this coupon.'), $coupon);
        }

        $eligible = $this->eligibleLines($coupon, $lines);
        if ($eligible->isEmpty()) {
            return CouponCheck::fail(__('Your cart has no products in this promotion yet.'), $coupon);
        }

        $minimum = Money::toSatang($coupon->min_subtotal);
        $eligibleTotal = $eligible->sum('lineTotal');
        if ($minimum > 0 && $eligibleTotal < $minimum) {
            return CouponCheck::fail(__('Add :amount more of the participating products to use this coupon.', [
                'amount' => Money::bahtFromSatang($minimum - $eligibleTotal),
            ]), $coupon);
        }

        return CouponCheck::pass($coupon);
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, CartLine>
     */
    public function eligibleLines(Coupon $coupon, Collection $lines): Collection
    {
        $productIds = $coupon->promotion->products->pluck('id');

        return $lines->filter(fn (CartLine $line) => $productIds->contains($line->product->id))->values();
    }
}
