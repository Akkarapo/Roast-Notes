<?php

namespace App\Http\Controllers;

use App\Actions\AddToCart;
use App\Enums\Grind;
use App\Enums\ProductType;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    public function store(AddToCartRequest $request, AddToCart $addToCart): RedirectResponse
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('variant_id'));

        $addToCart($request->user(), $variant, $request->enum('grind', Grind::class), $request->integer('quantity'));

        return back()->with('cart_added', $variant->displayName());
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $item->load('variant.product');
        $grind = $request->enum('grind', Grind::class) ?? $item->grind;
        if ($item->variant->product->type === ProductType::DripBag) {
            $grind = Grind::None;
        } elseif ($grind === Grind::None) {
            $grind = Grind::WholeBean;
        }
        $quantity = $request->has('quantity') ? $request->integer('quantity') : $item->quantity;

        DB::transaction(function () use ($item, $grind, $quantity) {
            // switching to a grind that already has its own line merges the two lines
            $twin = $item->cart->items()->whereKeyNot($item->id)
                ->where('product_variant_id', $item->product_variant_id)->where('grind', $grind->value)->first();

            if ($twin) {
                AddToCart::ensureQuantityAllowed($item->variant, $twin->quantity + $quantity);
                $twin->update(['quantity' => $twin->quantity + $quantity]);
                $item->delete();

                return;
            }

            AddToCart::ensureQuantityAllowed($item->variant, $quantity);
            $item->update(['grind' => $grind, 'quantity' => $quantity]);
        });

        return to_route('cart.index');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);
        $item->delete();

        return to_route('cart.index')->with('flash', __('Removed from cart.'));
    }
}
