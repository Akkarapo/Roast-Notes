<?php

namespace Database\Factories;

use App\Enums\BrewMethod;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'brew_method' => BrewMethod::PourOver,
            'comment' => 'หอมผลไม้ หวานยาว',
        ];
    }
}
