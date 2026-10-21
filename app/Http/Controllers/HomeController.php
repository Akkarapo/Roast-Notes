<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Still to come: "for you" (taste-profile)
        $newLots = Product::query()
            ->active()
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->sorted('newest')
            ->take(4)
            ->get();

        $bestSellers = Product::query()
            ->active()
            ->withUnitsSold()
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->having('units_sold', '>', 0)
            ->orderByDesc('units_sold')
            ->orderBy('products.id')
            ->take(4)
            ->get();

        return view('home', ['newLots' => $newLots, 'bestSellers' => $bestSellers]);
    }
}
