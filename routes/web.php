<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TasteQuizController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', HomeController::class)->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Member pages
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('/cart/coupon', [CouponController::class, 'store'])->name('cart.coupon.store');
    Route::delete('/cart/coupon', [CouponController::class, 'destroy'])->name('cart.coupon.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product:slug}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::get('/taste-quiz', [TasteQuizController::class, 'edit'])->name('taste-quiz.edit');
    Route::put('/taste-quiz', [TasteQuizController::class, 'update'])->name('taste-quiz.update');

    Route::get('/member', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/member', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/member', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
