<?php

namespace Tests\Unit\Catalog;

use App\Enums\BagSize;
use App\Enums\ProcessMethod;
use App\Enums\RoastLevel;
use App\Support\Catalog\DemoDataRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DemoDataRulesTest extends TestCase
{
    /** 3 notes per subcategory used by the weights table. */
    private function notes(): array
    {
        $subcategories = ['berry', 'dried-fruit', 'citrus', 'stone-fruit', 'tropical-fruit', 'blossom', 'black-tea', 'brown-sugar', 'honey', 'nutty', 'cocoa', 'fermented'];

        return collect($subcategories)->mapWithKeys(fn ($s) => [$s => ["$s-a", "$s-b", "$s-c"]])->all();
    }

    public function test_every_weight_row_sums_to_100(): void
    {
        foreach (DemoDataRules::SUBCATEGORY_WEIGHTS as $group => $tiers) {
            foreach ($tiers as $tier => $weights) {
                $this->assertSame(100, array_sum($weights), "$group/$tier");
            }
        }
    }

    #[DataProvider('tiers')]
    public function test_note_count_grows_with_flavor_score(float $flavor, int $expected): void
    {
        $notes = (new DemoDataRules('1'))->pickNotes(ProcessMethod::Washed, $flavor, $this->notes());

        $this->assertCount($expected, $notes);
        $this->assertSame($notes, array_unique($notes));
    }

    public static function tiers(): array
    {
        return ['7.83 → 2' => [7.83, 2], '7.99 → 2' => [7.99, 2], '8.00 → 3' => [8.0, 3], '8.49 → 3' => [8.49, 3], '8.50 → 4' => [8.5, 4]];
    }

    public function test_notes_only_come_from_weighted_subcategories(): void
    {
        $allowed = array_keys(DemoDataRules::weightsFor(ProcessMethod::Natural, 8.6)); // tropical, berry, blossom, stone

        for ($seed = 0; $seed < 50; $seed++) {
            foreach ((new DemoDataRules((string) $seed))->pickNotes(ProcessMethod::Natural, 8.6, $this->notes()) as $note) {
                $this->assertContains(substr($note, 0, -2), $allowed);
            }
        }
    }

    public function test_same_seed_gives_same_result(): void
    {
        $a = new DemoDataRules('41');
        $b = new DemoDataRules('41');

        $this->assertSame($a->pickNotes(ProcessMethod::Natural, 7.5, $this->notes()), $b->pickNotes(ProcessMethod::Natural, 7.5, $this->notes()));
        $this->assertSame($a->roastAgeDays(), $b->roastAgeDays());
    }

    #[DataProvider('ratios')]
    public function test_roast_level_from_acidity_body_ratio(float $acidity, float $body, RoastLevel $expected): void
    {
        $this->assertSame($expected, DemoDataRules::roastLevel($acidity, $body));
    }

    public static function ratios(): array
    {
        return [
            'ratio 1.06 light' => [8.48, 8.0, RoastLevel::Light],
            'ratio 1.05 medium' => [8.4, 8.0, RoastLevel::Medium],
            'ratio 1.00 medium' => [7.75, 7.75, RoastLevel::Medium],
            'ratio 0.95 medium' => [7.6, 8.0, RoastLevel::Medium],
            'ratio 0.94 dark' => [7.52, 8.0, RoastLevel::Dark],
        ];
    }

    #[DataProvider('prices')]
    public function test_hedonic_price_for_250g(float $points, ProcessMethod $process, string $country, int $expected): void
    {
        $this->assertSame($expected, DemoDataRules::price250($points, $process, $country));
    }

    public static function prices(): array
    {
        return [
            '82 washed' => [82, ProcessMethod::Washed, 'Colombia', 300],
            '86 washed' => [86, ProcessMethod::Washed, 'Colombia', 500],
            '90 washed' => [90, ProcessMethod::Washed, 'Colombia', 880],
            '86 natural Ethiopia' => [86, ProcessMethod::Natural, 'Ethiopia', 590], // 280 + 36*6*1.2*1.2 = 591
            'below 80 floors at base' => [79, ProcessMethod::Washed, 'Brazil', 280],
        ];
    }

    public function test_size_prices_scale_from_250g(): void
    {
        $this->assertSame(230, DemoDataRules::priceFor(BagSize::G100, 500)); // 225 → 230
        $this->assertSame(500, DemoDataRules::priceFor(BagSize::G250, 500));
        $this->assertSame(930, DemoDataRules::priceFor(BagSize::G500, 500)); // 925 → 930
        $this->assertSame(35, DemoDataRules::priceFor(BagSize::Sachet, 500)); // 33.6 → 35
    }

    public function test_roast_age_always_between_7_and_60_days_and_centres_near_15(): void
    {
        $ages = [];
        for ($seed = 0; $seed < 500; $seed++) {
            $ages[] = (new DemoDataRules((string) $seed))->roastAgeDays();
        }

        $this->assertGreaterThanOrEqual(7, min($ages));
        $this->assertLessThanOrEqual(60, max($ages));
        $this->assertEqualsWithDelta(15, array_sum($ages) / count($ages), 1.5);
    }

    public function test_nickname(): void
    {
        $this->assertSame('Floral Light', DemoDataRules::nickname('floral', RoastLevel::Light));
        $this->assertSame('Cocoa Comfort', DemoDataRules::nickname('nutty-cocoa', RoastLevel::Dark));
    }
}
