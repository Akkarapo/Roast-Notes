<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Http\Requests\CheckoutRequest;
use App\Support\Pricing\PriceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, PriceCalculator $calculator): View|RedirectResponse
    {
        $quote = $calculator->quote($request->user()->cartOrCreate());

        if ($quote->isEmpty() || $quote->lines->contains(fn ($line) => ! $line->item->isPurchasable())) {
            return to_route('cart.index');
        }

        return view('checkout.index', ['quote' => $quote, 'user' => $request->user()]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder($request->user(), $request->shippingDetails());
        } catch (ValidationException $e) {
            // stock or availability changed — send them back to the cart to fix it
            return to_route('cart.index')->withErrors($e->errors());
        }

        return to_route('orders.show', $order)->with('flash', __('Order placed! Thank you.'));
    }
}
