<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Models\FlavorCategory;
use App\Models\Origin;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request): View
    {
        $filters = $request->filters();

        $products = Product::query()
            ->active()
            ->filter($filters)
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->sorted($filters['sort'])
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'hasFilters' => $filters['flavor'] || $filters['roast'] || $filters['type'] || $filters['country'],
            'flavorCategories' => FlavorCategory::topLevel()->orderBy('id')->get(),
            'countries' => Origin::whereHas('products', fn (Builder $q) => $q->active())->distinct()->orderBy('country')->pluck('country'),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('products.show', ['product' => $product]);
    }
}
