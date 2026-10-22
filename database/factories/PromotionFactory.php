<?php

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\BuyXGetY;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => PromotionType::Coupon,
            'name' => 'Test promotion',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }

    /** A coupon promotion with its subtype row, e.g. ->coupon('TEST10', 10, ['usage_limit' => 5]) */
    public function coupon(string $code = 'TEST10', int $percent = 10, array $attributes = []): static
    {
        return $this->state(['type' => PromotionType::Coupon, 'name' => "$code $percent%"])
            ->afterCreating(fn (Promotion $p) => Coupon::create(['promotion_id' => $p->id, 'code' => $code, 'percent_off' => $percent] + $attributes));
    }

    public function buyXGetY(int $buy = 10, int $free = 2): static
    {
        return $this->state(['type' => PromotionType::BuyXGetY, 'name' => "ซื้อ $buy แถม $free"])
            ->afterCreating(fn (Promotion $p) => BuyXGetY::create(['promotion_id' => $p->id, 'buy_quantity' => $buy, 'free_quantity' => $free]));
    }

    /** @param iterable<Product> $products */
    public function forProducts(iterable $products): static
    {
        return $this->afterCreating(fn (Promotion $p) => $p->products()->attach(collect($products)->pluck('id')));
    }

    public function expired(): static
    {
        return $this->state(['starts_at' => now()->subMonths(3), 'ends_at' => now()->subDay()]);
    }
}
