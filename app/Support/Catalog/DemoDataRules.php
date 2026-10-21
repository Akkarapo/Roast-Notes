<?php

namespace App\Support\Catalog;

use App\Enums\BagSize;
use App\Enums\ProcessMethod;
use App\Enums\RoastLevel;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * DEMO DATA ONLY.
 *
 * CQI gives real origins and cupping scores but no flavor notes, roast level, price or roast date.
 * These rules (chosen by the project owner, see docs/specs/SPEC-catalog.md) fill those gaps.
 * Every random choice is seeded from the lot's CQI reference, so seeding twice gives the same catalog.
 */
class DemoDataRules
{
    // ---- Flavor notes: WCR lexicon + score-based probabilistic mapping ----

    /**
     * Subcategory weights (percent) by process group and Flavor-score tier.
     *
     * @var array<string, array<string, array<string, int>>>
     */
    public const SUBCATEGORY_WEIGHTS = [
        'natural' => [
            'low' => ['dried-fruit' => 60, 'cocoa' => 15, 'nutty' => 15, 'fermented' => 10],   // owner
            'mid' => ['berry' => 45, 'dried-fruit' => 30, 'cocoa' => 25],                       // proposed
            'high' => ['tropical-fruit' => 50, 'berry' => 20, 'blossom' => 20, 'stone-fruit' => 10], // owner
        ],
        'washed' => [
            'low' => ['cocoa' => 25, 'nutty' => 20, 'brown-sugar' => 35, 'citrus' => 20],
            'mid' => ['citrus' => 40, 'stone-fruit' => 30, 'brown-sugar' => 30],
            'high' => ['blossom' => 30, 'black-tea' => 20, 'citrus' => 30, 'berry' => 20],
        ],
        'other' => [ // honey, semi-washed, other
            'low' => ['brown-sugar' => 30, 'honey' => 20, 'nutty' => 30, 'stone-fruit' => 20],
            'mid' => ['brown-sugar' => 25, 'honey' => 15, 'stone-fruit' => 35, 'tropical-fruit' => 25],
            'high' => ['brown-sugar' => 25, 'honey' => 15, 'stone-fruit' => 35, 'tropical-fruit' => 25],
        ],
    ];

    /** Top-level category word + roast word make the handwritten nickname, e.g. "Floral Light". */
    public const NICKNAME_WORDS = [
        'category' => ['fruity' => 'Fruity', 'floral' => 'Floral', 'sweet' => 'Sweet', 'nutty-cocoa' => 'Cocoa', 'sour-fermented' => 'Wild'],
        'roast' => ['light' => 'Light', 'medium' => 'Delight', 'dark' => 'Comfort'],
    ];

    // ---- Price: hedonic (non-linear) model ----

    public const BASE_PRICE_250G = 280;

    public const PRICE_EXPONENT = 2.0;

    public const PRICE_MULTIPLIER = 6;

    public const PROCESS_MULTIPLIER = ['natural' => 1.2, 'honey' => 1.15, 'other' => 1.05, 'washed' => 1.0, 'semi_washed' => 1.0];

    public const ORIGIN_MULTIPLIER = ['Ethiopia' => 1.2, 'Kenya' => 1.2, 'Panama' => 1.2, 'United States (Hawaii)' => 1.4];

    /** Price of each bag size relative to the 250g bag. */
    public const SIZE_FACTOR = ['100g' => 0.45, '250g' => 1.0, '500g' => 1.85, 'sachet' => 12 / 250 * 1.4];

    // ---- Roast date: resting period + shelf life ----

    public const ROAST_AGE_MEAN = 15;

    public const ROAST_AGE_SD = 6;

    public const ROAST_AGE_MIN = 7;

    public const ROAST_AGE_MAX = 60;

    private Randomizer $random;

    public function __construct(string $seedKey)
    {
        $this->random = new Randomizer(new Mt19937(crc32('roast-notes:'.$seedKey)));
    }

    public static function flavorTier(float $flavorScore): string
    {
        return match (true) {
            $flavorScore >= 8.5 => 'high',
            $flavorScore >= 8.0 => 'mid',
            default => 'low',
        };
    }

    /** Higher Flavor score = more complex cup = more notes. */
    public static function noteCount(float $flavorScore): int
    {
        return ['low' => 2, 'mid' => 3, 'high' => 4][self::flavorTier($flavorScore)];
    }

    /** @return array<string, int> */
    public static function weightsFor(ProcessMethod $process, float $flavorScore): array
    {
        $group = in_array($process, [ProcessMethod::Natural, ProcessMethod::Washed], true) ? $process->value : 'other';

        return self::SUBCATEGORY_WEIGHTS[$group][self::flavorTier($flavorScore)];
    }

    /**
     * Pick distinct note names: draw a subcategory by weight, then an unused note inside it.
     *
     * @param  array<string, list<string>>  $notesBySubcategory  subcategory slug => note names
     * @return list<string>
     */
    public function pickNotes(ProcessMethod $process, float $flavorScore, array $notesBySubcategory): array
    {
        $weights = self::weightsFor($process, $flavorScore);
        $wanted = self::noteCount($flavorScore);
        $picked = [];

        for ($attempt = 0; count($picked) < $wanted && $attempt < 50; $attempt++) {
            $available = array_values(array_diff($notesBySubcategory[$this->weighted($weights)] ?? [], $picked));
            if ($available !== []) {
                $picked[] = $available[$this->random->getInt(0, count($available) - 1)];
            }
        }

        return $picked;
    }

    /** Acidity : Body ratio — acidity-led cups lean light, body-led cups lean dark. */
    public static function roastLevel(float $acidity, float $body): RoastLevel
    {
        $ratio = $acidity / $body;

        return match (true) {
            $ratio > 1.05 => RoastLevel::Light,
            $ratio < 0.95 => RoastLevel::Dark,
            default => RoastLevel::Medium,
        };
    }

    /** Price = Base + (Score − 80)^α × Multiplier × M_process × M_origin, for a 250g bag, rounded to 10. */
    public static function price250(float $totalPoints, ProcessMethod $process, string $country): int
    {
        $premium = max(0, $totalPoints - 80) ** self::PRICE_EXPONENT
            * self::PRICE_MULTIPLIER
            * self::PROCESS_MULTIPLIER[$process->value]
            * (self::ORIGIN_MULTIPLIER[$country] ?? 1.0);

        return (int) (round((self::BASE_PRICE_250G + $premium) / 10) * 10);
    }

    /** Bags round to 10 baht, single drip-bag sachets to 5 baht. */
    public static function priceFor(BagSize $size, int $price250): int
    {
        $raw = $price250 * self::SIZE_FACTOR[$size->value];
        $step = $size === BagSize::Sachet ? 5 : 10;

        return (int) max($step, round($raw / $step) * $step);
    }

    /** Days since roasting: Normal(15, 6) via Box–Muller, clamped to 7–60. */
    public function roastAgeDays(): int
    {
        $u1 = max($this->random->getFloat(0, 1), PHP_FLOAT_EPSILON);
        $u2 = $this->random->getFloat(0, 1);
        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

        return (int) min(self::ROAST_AGE_MAX, max(self::ROAST_AGE_MIN, round(self::ROAST_AGE_MEAN + $z * self::ROAST_AGE_SD)));
    }

    public static function nickname(string $topCategorySlug, RoastLevel $roast): string
    {
        return self::NICKNAME_WORDS['category'][$topCategorySlug].' '.self::NICKNAME_WORDS['roast'][$roast->value];
    }

    public function stock(): int
    {
        return $this->random->getInt(0, 9) === 0 ? 0 : $this->random->getInt(5, 40); // ~10% sold out
    }

    /** @param array<string, int> $weights */
    private function weighted(array $weights): string
    {
        $roll = $this->random->getInt(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}
