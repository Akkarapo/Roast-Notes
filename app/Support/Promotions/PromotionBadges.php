<?php

namespace App\Support\Promotions;

use App\Enums\PromotionType;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Promo labels for product cards, e.g. "ลด 15% · LIGHTROAST15" or "ซื้อ 10 แถม 2".
 * Loads every active promotion once per request, so a whole page of cards costs a fixed few queries.
 */
class PromotionBadges
{
    /** @return list<string> */
    public function for(Product $product): array
    {
        return $this->map()->get($product->id, []);
    }

    /** product_id => labels, cached on the current request */
    private function map(): Collection
    {
        $attributes = request()->attributes;
        if (! $attributes->has('promotion_badges')) {
            $attributes->set('promotion_badges', $this->build());
        }

        return $attributes->get('promotion_badges');
    }

    private function build(): Collection
    {
        return Promotion::query()
            ->currentlyActive()
            ->with(['coupon', 'buyXGetY', 'products:id'])
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Promotion $p) => $p->products->map(fn ($product) => [$product->id, $this->label($p)]))
            ->groupBy(0)
            ->map(fn (Collection $rows) => $rows->pluck(1)->all());
    }

    private function label(Promotion $promotion): string
    {
        return $promotion->type === PromotionType::Coupon
            ? __(':percent% off · :code', ['percent' => $promotion->coupon->percent_off, 'code' => $promotion->coupon->code])
            : __('Buy :buy get :free', ['buy' => $promotion->buyXGetY->buy_quantity, 'free' => $promotion->buyXGetY->free_quantity]);
    }
}
