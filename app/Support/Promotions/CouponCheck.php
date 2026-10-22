<?php

namespace App\Support\Promotions;

use App\Models\Coupon;

final readonly class CouponCheck
{
    private function __construct(public ?Coupon $coupon, public ?string $reason) {}

    public static function pass(Coupon $coupon): self
    {
        return new self($coupon, null);
    }

    public static function fail(string $reason, ?Coupon $coupon = null): self
    {
        return new self($coupon, $reason);
    }

    public function ok(): bool
    {
        return $this->reason === null;
    }
}
