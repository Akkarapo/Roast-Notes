<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveProduct;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\FlavorNote;
use App\Models\Origin;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public const LOW_STOCK = 5;

    public function index(Request $request): View
    {
        $type = ProductType::tryFrom((string) $request->query('type'));
        $state = in_array($request->query('state'), ['active', 'inactive', 'rare', 'low'], true) ? $request->query('state') : null;
        $search = trim((string) $request->query('q'));

        $products = Product::query()
            ->with(['origin', 'variants'])
            ->withMin('variants', 'price')
            ->withSum('variants', 'stock')
            ->withUnitsSold()
            ->when($type, fn (Builder $q) => $q->ofType($type))
            ->when($state === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($state === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when($state === 'rare', fn (Builder $q) => $q->exclusiveNow())
            ->when($state === 'low', fn (Builder $q) => $q->whereHas('variants', fn (Builder $v) => $v->where('stock', '<=', self::LOW_STOCK)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('nickname', 'like', "%{$search}%")
                ->orWhereHas('origin', fn (Builder $o) => $o->where('country', 'like', "%{$search}%"))))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'type', 'state', 'search') + ['lowStock' => self::LOW_STOCK]);
    }

    public function create(): View
    {
        return $this->form(new Product(['type' => ProductType::Bean, 'is_active' => true, 'roast_date' => today()]));
    }

    public function store(ProductRequest $request, SaveProduct $save): RedirectResponse
    {
        $product = $save(new Product, $request->productAttributes(), $request->validated('flavor_notes'), $request->enabledVariants());

        return to_route('admin.products.edit', $product)->with('flash', __(':product created.', ['product' => $product->name]));
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load(['variants', 'flavorNotes']));
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $save): RedirectResponse
    {
        $save($product, $request->productAttributes(), $request->validated('flavor_notes'), $request->enabledVariants());

        return to_route('admin.products.edit', $product)->with('flash', __(':product saved.', ['product' => $product->name]));
    }

    /** Only products that were never ordered can be deleted; the rest are deactivated instead. */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->hasBeenOrdered()) {
            throw ValidationException::withMessages(['product' => __('This product has orders — deactivate it instead.')])->status(422);
        }
        $product->delete();

        return to_route('admin.products.index')->with('flash', __(':product deleted.', ['product' => $product->name]));
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'origins' => Origin::orderBy('country')->orderBy('region')->get(),
            // notes grouped by their top-level WCR category (note → sub-category → top level)
            'noteGroups' => FlavorNote::with('category.parent')->orderBy('name')->get()
                ->groupBy(fn (FlavorNote $note) => ($note->category->parent ?? $note->category)->name),
            'ordered' => $product->exists && $product->hasBeenOrdered(),
        ]);
    }
}
