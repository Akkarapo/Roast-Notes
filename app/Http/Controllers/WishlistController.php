<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $products = $user->wishlist()
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->withRating()
            ->when($user->hasTasteProfile(), fn (Builder $q) => $q->withTasteMatch($user))
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('wishlist.index', ['products' => $products]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlist()->syncWithoutDetaching([$product->id]);

        return back()->with('flash', __(':product saved to your Wish List.', ['product' => $product->name]));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlist()->detach($product->id);

        return back()->with('flash', __(':product removed from your Wish List.', ['product' => $product->name]));
    }
}
