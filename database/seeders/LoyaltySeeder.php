<?php

namespace Database\Seeders;

use App\Actions\RecalculateMemberTier;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DEMO DATA (after OrderSeeder): tiers from the demo orders, and the 3 best-scoring beans as rare lots
 * that Roaster Masters can buy for the next 14 days. See docs/specs/SPEC-loyalty.md.
 */
class LoyaltySeeder extends Seeder
{
    public const RARE_LOTS = 3;

    public function run(RecalculateMemberTier $recalculate): void
    {
        User::where('role', 'member')->each(fn (User $user) => $recalculate($user));

        $rare = Product::ofType(ProductType::Bean)
            ->join('cupping_scores', 'cupping_scores.product_id', '=', 'products.id')
            ->orderByDesc('cupping_scores.total_points')
            ->limit(self::RARE_LOTS)
            ->pluck('products.id');

        Product::whereIn('id', $rare)->update(['exclusive_until' => now()->addDays(14)->endOfDay()]);
    }
}
