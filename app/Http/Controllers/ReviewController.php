<?php

namespace App\Http\Controllers;

use App\Enums\BrewMethod;
use App\Enums\ProductType;
use App\Http\Requests\ReviewRequest;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(Request $request, OrderItem $item): View
    {
        $item->load('order', 'variant.product');
        $this->authorize('create', [Review::class, $item]);

        return view('reviews.form', [
            'item' => $item,
            'review' => new Review(['brew_method' => $this->suggestedBrewMethod($request, $item)]),
        ]);
    }

    public function store(ReviewRequest $request, OrderItem $item): RedirectResponse
    {
        $item->load('order');
        $this->authorize('create', [Review::class, $item]);

        try {
            $item->review()->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            // double submit: the unique index on order_item_id is the final guard
            throw ValidationException::withMessages(['rating' => __('You have already reviewed this item.')]);
        }

        return to_route('orders.show', $item->order)->with('flash', __('Thanks for your review!'));
    }

    public function edit(Review $review): View
    {
        $review->load('orderItem.order', 'orderItem.variant.product');
        $this->authorize('update', $review);

        return view('reviews.form', ['item' => $review->orderItem, 'review' => $review]);
    }

    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);
        $review->update($request->validated());

        return to_route('orders.show', $review->orderItem->order)->with('flash', __('Review updated.'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        $order = $review->orderItem->order;
        $review->delete();

        return to_route('orders.show', $order)->with('flash', __('Review deleted.'));
    }

    /** Drip bags are brewed as drip bags; beans default to the member's usual method from the Taste Profile. */
    private function suggestedBrewMethod(Request $request, OrderItem $item): ?BrewMethod
    {
        return $item->variant->product->type === ProductType::DripBag
            ? BrewMethod::DripBag
            : $request->user()->preferred_brew_method;
    }
}
