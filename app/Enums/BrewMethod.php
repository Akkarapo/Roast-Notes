<?php

namespace App\Enums;

enum BrewMethod: string
{
    case PourOver = 'pour_over';
    case FrenchPress = 'french_press';
    case Espresso = 'espresso';
    case MokaPot = 'moka_pot';
    case AeroPress = 'aeropress';
    case ColdBrew = 'cold_brew';
    case DripBag = 'drip_bag';

    public function label(): string
    {
        return match ($this) {
            self::PourOver => 'Pour-over',
            self::FrenchPress => 'French press',
            self::Espresso => 'Espresso',
            self::MokaPot => 'Moka pot',
            self::AeroPress => 'AeroPress',
            self::ColdBrew => 'Cold brew',
            self::DripBag => 'ดริปแบ็ก',
        };
    }

    public function thaiHint(): string
    {
        return match ($this) {
            self::PourOver => 'ดริปมือ V60 / Kalita',
            self::FrenchPress => 'แช่แล้วกด',
            self::Espresso => 'เครื่องชงเอสเปรสโซ',
            self::MokaPot => 'หม้อต้มบนเตา',
            self::AeroPress => 'กระบอกอัดแรงดัน',
            self::ColdBrew => 'สกัดเย็นข้ามคืน',
            self::DripBag => 'ซองดริปสำเร็จรูป',
        };
    }

    /** Grind preselected in the add-to-cart form. */
    public function defaultGrind(): Grind
    {
        return match ($this) {
            self::Espresso, self::MokaPot => Grind::Espresso,
            self::PourOver, self::AeroPress, self::ColdBrew => Grind::Drip,
            self::FrenchPress, self::DripBag => Grind::WholeBean,
        };
    }
}
