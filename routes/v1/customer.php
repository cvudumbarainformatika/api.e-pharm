<?php

use App\Http\Controllers\Api\v1\CustomerController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'customer'
], function () {
    Route::get('/index', [CustomerController::class, 'index']);
    Route::post('/store', [CustomerController::class, 'store']);
    Route::post('/destroy', [CustomerController::class, 'destroy']);
});
