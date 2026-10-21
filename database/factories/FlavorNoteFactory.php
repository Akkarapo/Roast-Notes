<?php

namespace Database\Factories;

use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlavorNote>
 */
class FlavorNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            // a note always hangs off a subcategory, which hangs off a top-level category
            'flavor_category_id' => fn () => FlavorCategory::factory()->childOf(FlavorCategory::factory()->create()),
            'name' => ucfirst(fake()->unique()->word()).' '.fake()->randomNumber(3),
        ];
    }
}
