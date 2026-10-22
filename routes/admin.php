<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Loaded by bootstrap/app.php with prefix "/admin", name "admin." and
| middleware web + auth + admin. The admin module adds its pages here.
|
*/

Route::get('/', DashboardController::class)->name('index');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/advance', [OrderController::class, 'advance'])->name('orders.advance');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::resource('products', ProductController::class)->except('show');

Route::resource('promotions', PromotionController::class)->except('show');
Route::post('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::patch('/users/{user}/tier', [UserController::class, 'updateTier'])->name('users.tier');
Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
