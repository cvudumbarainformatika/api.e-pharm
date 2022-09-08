<?php

use App\Http\Controllers\Api\v1\ProductController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'produk'
], function () {
    Route::get('/index', [ProductController::class, 'index']);
    Route::get('/product', [ProductController::class, 'produk']);
    Route::post('/store', [ProductController::class, 'store']);
    Route::post('/destroy', [ProductController::class, 'destroy']);
});
