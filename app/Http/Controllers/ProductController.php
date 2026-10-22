<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Models\FlavorCategory;
use App\Models\Origin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request): View
    {
        $filters = $request->filters();
        $taster = $this->taster($request);
        if ($filters['sort'] === 'match' && ! $taster) {
            $filters['sort'] = 'newest';
        }

        $products = Product::query()
            ->active()
            ->filter($filters)
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->when($taster, fn (Builder $q) => $q->withTasteMatch($taster))
            ->when($filters['sort'] === 'match',
                fn (Builder $q) => $q->sortedByMatch($taster),
                fn (Builder $q) => $q->sorted($filters['sort']))
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'hasFilters' => $filters['flavor'] || $filters['roast'] || $filters['type'] || $filters['country'] || $filters['promo'],
            'flavorCategories' => FlavorCategory::topLevel()->orderBy('id')->get(),
            'countries' => Origin::whereHas('products', fn (Builder $q) => $q->active())->distinct()->orderBy('country')->pluck('country'),
            'canSortByMatch' => (bool) $taster,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['origin', 'flavorNotes.category.parent', 'variants', 'cupping']);

        $taster = $this->taster(request());

        return view('products.show', [
            'product' => $product,
            'similar' => $this->similar($product, $taster),
            'taster' => $taster,
        ]);
    }

    /**
     * Up to 4 other active products ranked by how many of their notes fall in the same
     * top-level flavor categories (e.g. both Fruity). The drip-bag twin of the same lot is skipped.
     */
    private function similar(Product $product, ?User $taster)
    {
        $topCategoryIds = $product->flavorNotes->pluck('category.parent_id')->unique()->values();

        return Product::query()
            ->active()
            ->whereKeyNot($product->id)
            ->where(fn (Builder $q) => $q->whereNull('cqi_ref')->orWhere('cqi_ref', '!=', $product->cqi_ref ?? ''))
            ->withCount(['flavorNotes as shared_notes_count' => fn (Builder $q) => $q->whereHas(
                'category', fn (Builder $c) => $c->whereIn('parent_id', $topCategoryIds)
            )])
            ->having('shared_notes_count', '>', 0)
            ->orderByDesc('shared_notes_count')
            ->orderByDesc('roast_date')
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->when($taster, fn (Builder $q) => $q->withTasteMatch($taster))
            ->take(4)
            ->get();
    }

    /** The signed-in member when they have a taste profile (match % is shown), otherwise null. */
    private function taster(Request $request): ?User
    {
        $user = $request->user();

        return $user?->hasTasteProfile() ? $user : null;
    }
}
