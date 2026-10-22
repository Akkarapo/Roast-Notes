<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Support\Pricing\PriceCalculator;
use App\Support\Promotions\CouponValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Coupon code entered on the cart page; kept in the session until the order is placed. */
class CouponController extends Controller
{
    public function store(Request $request, PriceCalculator $calculator, CouponValidator $coupons): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:30']], [], ['code' => 'โค้ดส่วนลด']);

        $code = Coupon::normalize($request->string('code'));
        $lines = $calculator->quote($request->user()->cartOrCreate())->lines;
        $check = $coupons->check($code, $request->user(), $lines);

        if (! $check->ok()) {
            return to_route('cart.index')->withErrors(['coupon' => $check->reason])->withInput();
        }

        $request->session()->put('coupon_code', $code);

        return to_route('cart.index')->with('flash', __('Coupon :code applied.', ['code' => $code]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('coupon_code');

        return to_route('cart.index');
    }
}
