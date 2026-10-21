<?php

namespace Database\Factories;

use App\Enums\BagSize;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => BagSize::G250,
            'weight_g' => 250,
            'price' => fake()->numberBetween(25, 90) * 10,
            'stock' => 20,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
        ];
    }
}
