<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // One seeder per module, in build order (see docs/specs/CAPABILITY-MAP.md)
        $this->call([
            UserSeeder::class,
            FlavorTaxonomySeeder::class,
            CatalogSeeder::class,
            TasteProfileSeeder::class,
            PromotionSeeder::class,
            OrderSeeder::class,
            LoyaltySeeder::class,
        ]);
    }
}
