<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Enums\PromotionType;
use App\Enums\RoastLevel;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

/**
 * Demo promotions agreed in docs/specs/SPEC-promotions.md.
 */
class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $beans = Product::ofType(ProductType::Bean)->pluck('id');
        $lightBeans = Product::ofType(ProductType::Bean)->roastLevels([RoastLevel::Light])->pluck('id');
        $dripBags = Product::ofType(ProductType::DripBag)->pluck('id');

        $this->coupon('คั่วอ่อนลด 15%', 'LIGHTROAST15', 15, $lightBeans, ['usage_limit' => 100, 'per_user_limit' => 1], now()->startOfDay(), now()->addDays(60)->endOfDay());
        $this->coupon('ลด 10% เมื่อซื้อเมล็ดครบ ฿500', 'ROASTER50', 10, $beans, ['min_subtotal' => 500, 'per_user_limit' => 3], now()->startOfDay(), null);
        $this->coupon('Summer sale (หมดอายุแล้ว)', 'SUMMER20', 20, $beans, [], now()->subMonths(4), now()->subMonths(1));

        $deal = Promotion::create([
            'type' => PromotionType::BuyXGetY,
            'name' => 'ดริปแบ็กซื้อ 10 แถม 2',
            'description' => 'ใส่ดริปแบ็กที่ร่วมรายการครบ 12 ซอง จ่ายแค่ 10',
            'starts_at' => now()->startOfDay(),
        ]);
        $deal->buyXGetY()->create(['buy_quantity' => 10, 'free_quantity' => 2]);
        $deal->products()->attach($dripBags);
    }

    private function coupon(string $name, string $code, int $percent, $productIds, array $limits, $startsAt, $endsAt): void
    {
        $promotion = Promotion::create(['type' => PromotionType::Coupon, 'name' => $name, 'starts_at' => $startsAt, 'ends_at' => $endsAt]);
        $promotion->coupon()->create(['code' => $code, 'percent_off' => $percent] + $limits);
        $promotion->products()->attach($productIds);
    }
}
