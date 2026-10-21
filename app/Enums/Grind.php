<?php

namespace App\Enums;

enum Grind: string
{
    case WholeBean = 'whole_bean';
    case Drip = 'drip';
    case Espresso = 'espresso';
    case None = 'none'; // drip bags are pre-ground

    public function label(): string
    {
        return match ($this) {
            self::WholeBean => 'เมล็ดเต็ม',
            self::Drip => 'บดดริป',
            self::Espresso => 'บดเอสเปรสโซ',
            self::None => '—',
        };
    }

    /** @return list<self> choices offered for whole-bean products */
    public static function forBeans(): array
    {
        return [self::WholeBean, self::Drip, self::Espresso];
    }
}
