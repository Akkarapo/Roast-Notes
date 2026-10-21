<?php

namespace App\Actions;

use App\Enums\Grind;
use App\Enums\ProductType;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddToCart
{
    /**
     * Add a variant to the member's cart. The same variant + grind goes onto the existing line.
     *
     * @throws ValidationException when the product is hidden or the total would exceed stock / the per-line limit
     */
    public function __invoke(User $user, ProductVariant $variant, ?Grind $grind, int $quantity): CartItem
    {
        $variant->loadMissing('product');

        if (! $variant->product->is_active) {
            throw ValidationException::withMessages(['variant_id' => __('This product is no longer available.')]);
        }

        // drip bags are pre-ground; beans default to whole bean
        $grind = $variant->product->type === ProductType::DripBag ? Grind::None : ($grind ?? Grind::WholeBean);
        if ($grind === Grind::None && $variant->product->type !== ProductType::DripBag) {
            $grind = Grind::WholeBean;
        }

        $item = $user->cartOrCreate()->items()->firstOrNew([
            'product_variant_id' => $variant->id,
            'grind' => $grind,
        ]);
        $total = ($item->exists ? $item->quantity : 0) + $quantity;

        self::ensureQuantityAllowed($variant, $total);

        $item->quantity = $total;
        $item->save();

        return $item;
    }

    /** @throws ValidationException */
    public static function ensureQuantityAllowed(ProductVariant $variant, int $quantity): void
    {
        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => $variant->stock === 0
                ? __('This size is sold out.')
                : __('Only :stock left in stock.', ['stock' => $variant->stock])]);
        }

        if ($quantity > CartItem::MAX_QUANTITY) {
            throw ValidationException::withMessages(['quantity' => __('You can order up to :max per item.', ['max' => CartItem::MAX_QUANTITY])]);
        }
    }
}
