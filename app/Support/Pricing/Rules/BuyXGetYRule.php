<?php

namespace App\Support\Pricing\Rules;

use App\Models\BuyXGetY;
use App\Support\Pricing\CartLine;
use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\DiscountRule;
use App\Support\Pricing\PricingContext;
use Illuminate\Support\Collection;

/**
 * Buy X get Y: for every X + Y participating units in the cart, the Y cheapest units are free.
 * Runs first in the pipeline.
 */
class BuyXGetYRule implements DiscountRule
{
    public function apply(PricingContext $context): array
    {
        $discounts = [];

        foreach ($this->activeDeals() as $deal) {
            $units = $this->units($deal, $context->lines);
            $free = intdiv($units->count(), $deal->groupSize()) * $deal->free_quantity;
            if ($free === 0) {
                continue;
            }

            $perLine = $units->sortBy('price')->take($free)
                ->groupBy('item')->map(fn (Collection $u) => $u->sum('price'))->all();

            $discounts[] = new DiscountLine('buy_x_get_y', $deal->promotion->name, array_sum($perLine), [
                'promotion_id' => $deal->promotion_id,
                'lines' => $perLine,
            ]);
        }

        return $discounts;
    }

    /**
     * Nudges for deals that are almost complete, e.g. "เพิ่มอีก 2 ชิ้น รับฟรี 2 ชิ้น".
     *
     * @param  Collection<int, CartLine>  $lines
     * @return list<array{promotion: string, add: int, free: int}>
     */
    public function hints(Collection $lines): array
    {
        $hints = [];
        foreach ($this->activeDeals() as $deal) {
            $remainder = $this->units($deal, $lines)->count() % $deal->groupSize();
            if ($remainder >= $deal->buy_quantity) {
                $hints[] = ['promotion' => $deal->promotion->name, 'add' => $deal->groupSize() - $remainder, 'free' => $deal->free_quantity];
            }
        }

        return $hints;
    }

    /** @return Collection<int, BuyXGetY> */
    private function activeDeals(): Collection
    {
        return BuyXGetY::query()
            ->whereHas('promotion', fn ($q) => $q->currentlyActive())
            ->with(['promotion.products:id'])
            ->get();
    }

    /**
     * One entry per participating unit in the cart.
     *
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, array{item: int, price: int}>
     */
    private function units(BuyXGetY $deal, Collection $lines): Collection
    {
        $productIds = $deal->promotion->products->pluck('id');

        return $lines
            ->filter(fn (CartLine $line) => $productIds->contains($line->product->id))
            ->flatMap(fn (CartLine $line) => array_fill(0, $line->quantity, ['item' => $line->item->id, 'price' => $line->unitPrice]))
            ->values();
    }
}
