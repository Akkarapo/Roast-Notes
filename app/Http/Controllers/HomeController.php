<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Sections still to come: best sellers (ordering) and "for you" (taste-profile)
        $newLots = Product::query()
            ->active()
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->sorted('newest')
            ->take(4)
            ->get();

        return view('home', ['newLots' => $newLots]);
    }
}
