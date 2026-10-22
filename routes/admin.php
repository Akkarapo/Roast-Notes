<?php

use App\Http\Controllers\Admin\OrderController;
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

Route::view('/', 'admin.index')->name('index');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/advance', [OrderController::class, 'advance'])->name('orders.advance');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
