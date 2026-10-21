<?php

namespace Tests\Feature\TasteProfile\Concerns;

use App\Enums\BrewMethod;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\FlavorTaxonomySeeder;

trait BuildsTasteProfiles
{
    protected function seedTaxonomy(): void
    {
        $this->seed(FlavorTaxonomySeeder::class);
    }

    protected function taster(array $slugs, ?RoastLevel $roast = null, BrewMethod $brew = BrewMethod::PourOver): User
    {
        $user = User::factory()->create();
        $user->tastePreferences()->sync(FlavorCategory::whereIn('slug', $slugs)->pluck('id'));
        $user->forceFill(['preferred_roast_level' => $roast, 'preferred_brew_method' => $brew, 'taste_quiz_completed_at' => now()])->save();

        return $user->fresh();
    }

    /** @param list<string> $notes note names from the WCR taxonomy */
    protected function beanWith(string $name, array $notes, array $attributes = []): Product
    {
        $product = Product::factory()->withVariants()->create(['name' => $name, 'slug' => str($name)->slug()] + $attributes);
        $product->flavorNotes()->attach(FlavorNote::whereIn('name', $notes)->pluck('id'));

        return $product;
    }
}
