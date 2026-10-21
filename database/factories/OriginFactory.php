<?php

namespace Database\Factories;

use App\Models\Origin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Origin>
 */
class OriginFactory extends Factory
{
    public function definition(): array
    {
        return [
            'country' => fake()->randomElement(['Ethiopia', 'Colombia', 'Thailand', 'Kenya', 'Brazil']),
            'region' => fake()->unique()->city(),
        ];
    }
}
