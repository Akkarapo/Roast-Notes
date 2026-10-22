<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Http\Requests\CheckoutRequest;
use App\Support\Pricing\PriceCalculator;
use App\Support\Promotions\CouponValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, PriceCalculator $calculator, CouponValidator $coupons): View|RedirectResponse
    {
        $code = $request->session()->get('coupon_code');
        $quote = $calculator->quote($request->user()->cartOrCreate(), $code);

        if ($quote->isEmpty() || $quote->lines->contains(fn ($line) => ! $line->item->isPurchasable())) {
            return to_route('cart.index');
        }

        // a coupon that stopped working has to be fixed on the cart page (checkout only shows it)
        if ($code && ($problem = $coupons->check($code, $request->user(), $quote->lines)->reason)) {
            return to_route('cart.index')->withErrors(['coupon' => $problem]);
        }

        return view('checkout.index', ['quote' => $quote, 'user' => $request->user(), 'couponCode' => $code]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder($request->user(), $request->shippingDetails(), $request->session()->get('coupon_code'));
        } catch (ValidationException $e) {
            // stock or availability changed — send them back to the cart to fix it
            return to_route('cart.index')->withErrors($e->errors());
        }

        $request->session()->forget('coupon_code');

        return to_route('orders.show', $order)->with('flash', __('Order placed! Thank you.'))->with('order_placed', true);
    }
}
