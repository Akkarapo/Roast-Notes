<?php

namespace App\Enums;

enum ProcessMethod: string
{
    case Washed = 'washed';
    case Natural = 'natural';
    case Honey = 'honey';
    case SemiWashed = 'semi_washed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Washed => 'Washed',
            self::Natural => 'Natural',
            self::Honey => 'Honey',
            self::SemiWashed => 'Semi-washed',
            self::Other => 'Other',
        };
    }
}
