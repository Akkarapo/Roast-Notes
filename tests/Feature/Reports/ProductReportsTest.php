<?php

namespace Tests\Feature\Reports;

use App\Enums\OrderStatus;
use App\Enums\RoastLevel;
use App\Support\Reports\FlavorNotes;
use App\Support\Reports\RoastShare;
use App\Support\Reports\TopProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Reports\Concerns\SellsThings;
use Tests\TestCase;

/** Best sellers, roast share and flavor notes against numbers worked out by hand. */
class ProductReportsTest extends TestCase
{
    use RefreshDatabase, SellsThings;

    public function test_top_products_rank_by_units_and_skip_cancelled_and_out_of_range(): void
    {
        $a = $this->lot('Alpha', RoastLevel::Light);
        $b = $this->lot('Bravo', RoastLevel::Dark);
        $this->sell($a, 3, 900);
        $this->sell($a, 2, 600);
        $this->sell($b, 4, 1600);
        $this->sell($b, 9, 2700, OrderStatus::Cancelled);
        $this->sell($b, 9, 2700, at: '2026-09-01 10:00');

        $rows = app(TopProducts::class)->rows($this->october());
        $this->assertSame([['Alpha', 5, 150000], ['Bravo', 4, 160000]], array_map(fn ($r) => [$r['name'], $r['units'], $r['revenue']], $rows));
    }

    public function test_roast_share(): void
    {
        $this->sell($this->lot('L', RoastLevel::Light), 1, 300);
        $this->sell($this->lot('D', RoastLevel::Dark), 3, 900);

        $rows = app(RoastShare::class)->rows($this->october());
        $this->assertSame([['light', 1, 25.0], ['medium', 0, 0.0], ['dark', 3, 75.0]], array_map(fn ($r) => [$r['roast']->value, $r['units'], $r['share']], $rows));
    }

    public function test_flavor_notes_and_groups_count_a_line_once_per_group(): void
    {
        [, [$berry, $cherry]] = $this->group('fruity', 'Fruity', ['Berry', 'Cherry']);
        [, [$cocoa]] = $this->group('nutty-cocoa', 'Nutty/Cocoa', ['Cocoa']);
        $this->sell($this->lot('Two fruits', RoastLevel::Light, [$berry, $cherry]), 2, 600);   // 2 units, both notes in Fruity
        $this->sell($this->lot('Fruit and cocoa', RoastLevel::Medium, [$cherry, $cocoa]), 1, 300);

        $data = app(FlavorNotes::class)->data($this->october());
        $this->assertSame(['Cherry' => 3, 'Berry' => 2, 'Cocoa' => 1], collect($data['notes'])->pluck('units', 'name')->all());
        // Fruity: 2 + 1 = 3 units (not 2+2+1), 3 of 3 units = 100%; Nutty/Cocoa: 1 of 3
        $this->assertSame(['Fruity' => [3, 100.0], 'Nutty/Cocoa' => [1, 33.3]], collect($data['groups'])->mapWithKeys(fn ($g) => [$g['name'] => [$g['units'], $g['share']]])->all());
    }
}
