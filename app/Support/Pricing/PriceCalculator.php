<?php

namespace App\Support\Pricing;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;

class PriceCalculator
{
    /** @var list<DiscountRule> */
    private array $rules;

    /** @param list<DiscountRule>|null $rules defaults to config('roastnotes.discount_rules') */
    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? array_map(fn (string $class) => app($class), config('roastnotes.discount_rules', []));
    }

    public function quote(Cart $cart, ?string $couponCode = null): Quote
    {
        $cart->loadForPricing();

        return $this->quoteItems($cart->user, $cart->items, $couponCode);
    }

    /** @param Collection<int, CartItem> $items */
    public function quoteItems(User $user, Collection $items, ?string $couponCode = null): Quote
    {
        $lines = $items->map(fn (CartItem $item) => new CartLine($item))->values();
        $subtotal = $lines->sum('lineTotal');
        $context = new PricingContext($user, $lines, $subtotal, 0, $couponCode);

        $discounts = [];
        $discountTotal = 0;
        foreach ($this->rules as $rule) {
            foreach ($rule->apply($context->withDiscounted($discountTotal)) as $line) {
                // never discount more than what is left of the subtotal
                $amount = min(max(0, $line->amount), $subtotal - $discountTotal);
                if ($amount > 0) {
                    $discounts[] = $line->withAmount($amount);
                    $discountTotal += $amount;
                }
            }
        }

        $threshold = Money::toSatang(config('roastnotes.free_shipping_threshold'));
        $shipping = $lines->isEmpty() || $subtotal - $discountTotal >= $threshold
            ? 0
            : Money::toSatang(config('roastnotes.shipping_fee'));

        return new Quote($lines, $subtotal, $discounts, $discountTotal, $shipping, $subtotal - $discountTotal + $shipping, $threshold);
    }
}
