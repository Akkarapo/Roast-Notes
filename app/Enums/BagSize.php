<?php

namespace App\Enums;

enum BagSize: string
{
    case G100 = '100g';
    case G250 = '250g';
    case G500 = '500g';
    case Sachet = 'sachet';

    public function label(): string
    {
        return $this === self::Sachet ? '1 ซอง' : $this->value;
    }

    public function grams(): int
    {
        return match ($this) {
            self::G100 => 100,
            self::G250 => 250,
            self::G500 => 500,
            self::Sachet => 12,
        };
    }

    /** @return list<self> */
    public static function forBeans(): array
    {
        return [self::G100, self::G250, self::G500];
    }
}
