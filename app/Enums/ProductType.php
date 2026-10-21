<?php

namespace App\Enums;

enum ProductType: string
{
    case Bean = 'bean';
    case DripBag = 'drip_bag';

    public function label(): string
    {
        return match ($this) {
            self::Bean => 'เมล็ด',
            self::DripBag => 'ดริปแบ็ก',
        };
    }
}
