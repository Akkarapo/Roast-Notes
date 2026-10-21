<?php

namespace App\Enums;

enum RoastLevel: string
{
    case Light = 'light';
    case Medium = 'medium';
    case Dark = 'dark';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function thaiLabel(): string
    {
        return match ($this) {
            self::Light => 'คั่วอ่อน',
            self::Medium => 'คั่วกลาง',
            self::Dark => 'คั่วเข้ม',
        };
    }
}
