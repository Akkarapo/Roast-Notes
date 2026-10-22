<?php

namespace App\Providers;

use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Item count on the navbar cart link (one query, members only)
        View::composer('layouts.navigation', function ($view) {
            $view->with('cartCount', Auth::check()
                ? (int) CartItem::whereHas('cart', fn ($q) => $q->where('user_id', Auth::id()))->sum('quantity')
                : 0);
            // red dot on the user menu until the Taste Quiz is done
            $view->with('needsTasteQuiz', Auth::check() && ! Auth::user()->isAdmin() && ! Auth::user()->hasTasteProfile());
        });
    }
}
