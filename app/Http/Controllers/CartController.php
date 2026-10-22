<?php

namespace App\Http\Controllers;

use App\Support\Loyalty\LoyaltyStatus;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\Rules\BuyXGetYRule;
use App\Support\Promotions\CouponValidator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, PriceCalculator $calculator, CouponValidator $coupons, BuyXGetYRule $deals): View
    {
        $code = $request->session()->get('coupon_code');
        $quote = $calculator->quote($request->user()->cartOrCreate(), $code);

        return view('cart.index', [
            'quote' => $quote,
            'canCheckout' => ! $quote->isEmpty() && $quote->lines->every(fn ($line) => $line->item->isPurchasable()),
            'couponCode' => $code,
            // the code may have stopped working since it was entered (cart changed, expired, ...)
            'couponProblem' => $code && ! $quote->isEmpty() ? $coupons->check($code, $request->user(), $quote->lines)->reason : null,
            'dealHints' => $deals->hints($quote->lines),
            'loyalty' => LoyaltyStatus::for($request->user()),
        ]);
    }
}
