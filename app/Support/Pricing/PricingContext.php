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
     */
    public function __construct(
        public User $user,
        public Collection $lines,
        public int $subtotal,
        public int $discountedSoFar = 0,
        public ?string $couponCode = null,
    ) {}

    public function remaining(): int
    {
        return max(0, $this->subtotal - $this->discountedSoFar);
    }

    public function withDiscounted(int $discountedSoFar): self
    {
        return new self($this->user, $this->lines, $this->subtotal, $discountedSoFar, $this->couponCode);
    }
}
