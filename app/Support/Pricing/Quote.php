<?php

namespace App\Support\Pricing;

use Illuminate\Support\Collection;

/**
 * Result of pricing a cart. Cart and checkout pages both render this, so their numbers always match.
 * All amounts are satang.
 */
final readonly class Quote
{
    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  list<DiscountLine>  $discounts
     */
    public function __construct(
        public Collection $lines,
        public int $subtotal,
        public array $discounts,
        public int $discountTotal,
        public int $shippingFee,
        public int $total,
        public int $freeShippingThreshold,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /** Satang still needed for free shipping, or null when it already applies. */
    public function freeShippingGap(): ?int
    {
        return $this->shippingFee > 0 ? $this->freeShippingThreshold - ($this->subtotal - $this->discountTotal) : null;
    }

    public function itemCount(): int
    {
        return $this->lines->sum('quantity');
    }
}
