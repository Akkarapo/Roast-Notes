<?php

namespace App\Support\Pricing;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read-only view of the cart handed to each DiscountRule. All amounts are satang.
 */
final readonly class PricingContext
{
    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  int  $discountedSoFar  total given by earlier rules (loyalty discounts what is left)
     * @param  list<DiscountLine>  $appliedDiscounts  discounts from earlier rules (coupon skips units already free)
     */
    public function __construct(
        public User $user,
        public Collection $lines,
        public int $subtotal,
        public int $discountedSoFar = 0,
        public ?string $couponCode = null,
        public array $appliedDiscounts = [],
    ) {}

    public function remaining(): int
    {
        return max(0, $this->subtotal - $this->discountedSoFar);
    }

    /** @param list<DiscountLine> $appliedDiscounts */
    public function withDiscounted(int $discountedSoFar, array $appliedDiscounts = []): self
    {
        return new self($this->user, $this->lines, $this->subtotal, $discountedSoFar, $this->couponCode, $appliedDiscounts);
    }

    /** Satang already taken off one cart line by earlier rules (from DiscountLine meta['lines']). */
    public function discountedOnLine(int $cartItemId): int
    {
        return array_sum(array_map(fn (DiscountLine $d) => $d->meta['lines'][$cartItemId] ?? 0, $this->appliedDiscounts));
    }
}
