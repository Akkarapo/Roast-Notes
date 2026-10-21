<?php

namespace Database\Factories;

use App\Models\CuppingScore;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuppingScore>
 */
class CuppingScoreFactory extends Factory
{
    public function definition(): array
    {
        $scores = [];
        foreach (CuppingScore::ATTRIBUTES as $attribute) {
            $scores[$attribute] = in_array($attribute, ['uniformity', 'clean_cup', 'sweetness'], true)
                ? 10 : fake()->randomFloat(2, 7.25, 8.75);
        }

        return ['product_id' => Product::factory()] + $scores + [
            'total_points' => round(array_sum($scores), 2),
            'graded_on' => fake()->date(),
        ];
    }
}
