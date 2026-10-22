<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BrewMethod;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $rating = in_array((int) $request->query('rating'), [1, 2, 3, 4, 5], true) ? (int) $request->query('rating') : null;
        $brew = BrewMethod::tryFrom((string) $request->query('brew'));
        $search = trim((string) $request->query('q'));

        $reviews = Review::query()
            ->with(['orderItem.order.user', 'orderItem.variant.product'])
            ->when($rating, fn (Builder $q) => $q->where('rating', $rating))
            ->when($brew, fn (Builder $q) => $q->where('brew_method', $brew))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('comment', 'like', "%{$search}%")
                ->orWhereHas('orderItem', fn (Builder $i) => $i->where('product_name', 'like', "%{$search}%"))))
            ->latest()->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'rating', 'brew', 'search'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('flash', __('Review deleted.'));
    }
}
