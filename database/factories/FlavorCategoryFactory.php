<?php

namespace Database\Factories;

use App\Models\FlavorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FlavorCategory>
 */
class FlavorCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->randomNumber(4);

        return ['parent_id' => null, 'slug' => Str::slug($name), 'name' => ucfirst($name)];
    }

    public function childOf(FlavorCategory $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
