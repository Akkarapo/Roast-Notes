<?php

namespace App\Support\Pricing;

final readonly class DiscountLine
{
    /**
     * @param  string  $source  e.g. coupon, buy_x_get_y, tier
     * @param  string  $label  shown to the customer, e.g. "LIGHTROAST15 ลด 15%"
     * @param  int  $amount  satang, positive
     * @param  array<string, mixed>  $meta  extra data a rule wants persisted later (e.g. promotion_id)
     */
    public function __construct(
        public string $source,
        public string $label,
        public int $amount,
        public array $meta = [],
    ) {}

    public function withAmount(int $amount): self
    {
        return new self($this->source, $this->label, $amount, $this->meta);
    }
}
