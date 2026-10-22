<?php

namespace App\Support\Pricing;

final readonly class DiscountLine
{
    /**
     * @param  string  $source  e.g. coupon, buy_x_get_y, tier
     * @param  string  $label  shown to the customer, e.g. "LIGHTROAST15 ลด 15%"
     * @param  int  $amount  satang, positive
     * @param  array{promotion_id?: int, lines?: array<int, int>}  $meta  promotion_id is saved on the order;
     *                                                                    lines = cart_item_id => satang taken off that line
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

    public function promotionId(): ?int
    {
        return $this->meta['promotion_id'] ?? null;
    }
}
