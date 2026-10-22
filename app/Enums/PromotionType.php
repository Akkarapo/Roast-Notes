<?php

namespace App\Enums;

enum PromotionType: string
{
    case Coupon = 'coupon';
    case BuyXGetY = 'buy_x_get_y';

    public function label(): string
    {
        return match ($this) {
            self::Coupon => 'คูปองส่วนลด',
            self::BuyXGetY => 'ซื้อ X แถม Y',
        };
    }
}
