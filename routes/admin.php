<?php

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
