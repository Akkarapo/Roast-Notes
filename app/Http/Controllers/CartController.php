<?php

namespace App\Http\Controllers;

use App\Support\Pricing\PriceCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, PriceCalculator $calculator): View
    {
        $quote = $calculator->quote($request->user()->cartOrCreate());

        return view('cart.index', [
            'quote' => $quote,
            'canCheckout' => ! $quote->isEmpty() && $quote->lines->every(fn ($line) => $line->item->isPurchasable()),
        ]);
    }
}
