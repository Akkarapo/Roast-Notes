<?php

namespace Tests\Unit\TasteProfile;

use App\Enums\BrewMethod;
use App\Enums\Grind;
use PHPUnit\Framework\TestCase;

class DefaultGrindTest extends TestCase
{
    public function test_brew_method_picks_a_sensible_grind(): void
    {
        $this->assertSame(Grind::Espresso, BrewMethod::Espresso->defaultGrind());
        $this->assertSame(Grind::Espresso, BrewMethod::MokaPot->defaultGrind());
        $this->assertSame(Grind::Drip, BrewMethod::PourOver->defaultGrind());
        $this->assertSame(Grind::Drip, BrewMethod::AeroPress->defaultGrind());
        $this->assertSame(Grind::Drip, BrewMethod::ColdBrew->defaultGrind());
        $this->assertSame(Grind::WholeBean, BrewMethod::FrenchPress->defaultGrind());
        $this->assertSame(Grind::WholeBean, BrewMethod::DripBag->defaultGrind());
    }
}
