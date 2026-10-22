<?php

namespace App\Support\Community;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Which products the signed-in member has saved — one query per request, shared by every heart on the page.
 */
class WishlistState
{
    public function has(int $productId): bool
    {
        return $this->ids()->has($productId);
    }

    public function count(): int
    {
        return $this->ids()->count();
    }

    /** @return Collection<int, true> product_id => true */
    private function ids(): Collection
    {
        $attributes = request()->attributes;
        if (! $attributes->has('wishlist_ids')) {
            $attributes->set('wishlist_ids', Auth::check()
                ? DB::table('wishlists')->where('user_id', Auth::id())->pluck('product_id')->flip()->map(fn () => true)
                : collect());
        }

        return $attributes->get('wishlist_ids');
    }
}
