<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::query()
            ->with(['coupon', 'buyXGetY'])
            ->withCount('products')
            ->withTimesUsed()
            ->latest('starts_at')->latest('id')
            ->paginate(20);

        return view('admin.promotions.index', ['promotions' => $promotions]);
    }

    public function create(Request $request): View
    {
        $type = PromotionType::tryFrom((string) $request->query('type')) ?? PromotionType::Coupon;

        return $this->form(new Promotion(['type' => $type, 'is_active' => true, 'starts_at' => today()]));
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $promotion = $this->save(new Promotion(['type' => $request->type()]), $request);

        return to_route('admin.promotions.index')->with('flash', __(':promotion created.', ['promotion' => $promotion->name]));
    }

    public function edit(Promotion $promotion): View
    {
        return $this->form($promotion->load(['coupon', 'buyXGetY', 'products:id']));
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->save($promotion, $request);

        return to_route('admin.promotions.index')->with('flash', __(':promotion saved.', ['promotion' => $promotion->name]));
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back()->with('flash', $promotion->is_active
            ? __(':promotion switched on.', ['promotion' => $promotion->name])
            : __(':promotion switched off.', ['promotion' => $promotion->name]));
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->hasBeenUsed()) {
            throw ValidationException::withMessages(['promotion' => __('This promotion has been used in orders — switch it off instead.')]);
        }
        $promotion->delete(); // subtype row + participating products cascade

        return to_route('admin.promotions.index')->with('flash', __(':promotion deleted.', ['promotion' => $promotion->name]));
    }

    /** Supertype row + its one subtype row + participating products, all or nothing. */
    private function save(Promotion $promotion, PromotionRequest $request): Promotion
    {
        return DB::transaction(function () use ($promotion, $request) {
            $promotion->fill($request->promotionAttributes())->save();
            $relation = $promotion->type === PromotionType::Coupon ? $promotion->coupon() : $promotion->buyXGetY();
            $relation->updateOrCreate(['promotion_id' => $promotion->id], $request->subtypeAttributes());
            $promotion->products()->sync($request->validated('products'));

            return $promotion;
        });
    }

    private function form(Promotion $promotion): View
    {
        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'productGroups' => Product::query()->orderBy('type')->orderBy('roast_level')->orderBy('name')->get(['id', 'name', 'type', 'roast_level', 'is_active'])
                ->groupBy(fn (Product $p) => $p->type->label().($p->type->value === 'bean' ? ' · '.$p->roast_level->label() : '')),
        ]);
    }
}
