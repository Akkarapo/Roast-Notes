<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $taster = $user?->hasTasteProfile() ? $user : null;

        return view('home', [
            'taster' => $taster,
            'showQuizPrompt' => $user !== null && $taster === null,
            'forYou' => $taster ? $this->cards($taster)
                ->whereHas('variants', fn (Builder $q) => $q->where('stock', '>', 0))
                ->having('notes_liked', '>', 0)
                ->sortedByMatch($taster)
                ->take(4)
                ->get() : collect(),
            'bestSellers' => $this->cards($taster)
                ->withUnitsSold()
                ->having('units_sold', '>', 0)
                ->orderByDesc('units_sold')
                ->orderBy('products.id')
                ->take(4)
                ->get(),
            'newLots' => $this->cards($taster)->sorted('newest')->take(4)->get(),
        ]);
    }

    /** Base query for a row of product cards (with match % for members who did the quiz). */
    private function cards(?User $taster): Builder
    {
        return Product::query()
            ->active()
            ->with(['origin', 'flavorNotes', 'variants'])
            ->withMin('variants', 'price')
            ->when($taster, fn (Builder $q) => $q->withTasteMatch($taster));
    }
}
