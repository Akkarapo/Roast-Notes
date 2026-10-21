<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::view('/', 'home')->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Member pages
Route::middleware('auth')->group(function () {
    Route::view('/cart', 'cart.index')->name('cart.index'); // filled by ordering module

    Route::get('/member', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/member', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/member', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
