<?php

namespace Database\Factories;

use App\Enums\BagSize;
use App\Enums\ProcessMethod;
use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\Origin;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'origin_id' => Origin::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'nickname' => 'Test Notes',
            'type' => ProductType::Bean,
            'farm_name' => fake()->company(),
            'variety' => fake()->randomElement(['Bourbon', 'Typica', 'Caturra', 'Gesha']),
            'process' => fake()->randomElement(ProcessMethod::cases()),
            'altitude_m' => fake()->numberBetween(900, 2200),
            'roast_level' => fake()->randomElement(RoastLevel::cases()),
            'roast_date' => now()->subDays(fake()->numberBetween(7, 40))->toDateString(),
            'is_active' => true,
        ];
    }

    public function dripBag(): static
    {
        return $this->state(fn () => ['type' => ProductType::DripBag]);
    }

    /** Rare lot: only early-access tiers may buy it until $until (default: two weeks from now). */
    public function exclusive($until = null): static
    {
        return $this->state(fn (array $attributes) => ['exclusive_until' => $until ?? now()->addDays(14)]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Beans get 100g/250g/500g, drip bags a single sachet. */
    public function withVariants(int $basePrice = 200, int $stock = 20): static
    {
        return $this->afterCreating(function (Product $product) use ($basePrice, $stock) {
            $sizes = $product->type === ProductType::DripBag ? [BagSize::Sachet] : BagSize::forBeans();
            foreach ($sizes as $i => $size) {
                ProductVariant::factory()->for($product)->create([
                    'size' => $size,
                    'weight_g' => $size->grams(),
                    'price' => $basePrice * ($i + 1),
                    'stock' => $stock,
                    'sku' => strtoupper($product->slug.'-'.$size->value),
                ]);
            }
        });
    }
}
