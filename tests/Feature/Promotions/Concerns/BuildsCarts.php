<?php

namespace Tests\Feature\Promotions\Concerns;

use App\Enums\Grind;
use App\Models\Product;
use App\Models\User;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\Quote;

trait BuildsCarts
{
    protected User $member;

    protected function bean(int $price, array $attributes = []): Product
    {
        return Product::factory()->withVariants($price, stock: 50)->create($attributes);
    }

    protected function drip(int $price): Product
    {
        return Product::factory()->dripBag()->withVariants($price, stock: 50)->create();
    }

    /** Put the product's smallest size in the member's cart. */
    protected function add(Product $product, int $quantity, Grind $grind = Grind::WholeBean): void
    {
        $this->member->cartOrCreate()->items()->create([
            'product_variant_id' => $product->variants->first()->id,
            'grind' => $product->type->value === 'drip_bag' ? Grind::None : $grind,
            'quantity' => $quantity,
        ]);
    }

    protected function quote(?string $coupon = null): Quote
    {
        return app(PriceCalculator::class)->quote($this->member->cartOrCreate()->fresh(), $coupon);
    }
}
