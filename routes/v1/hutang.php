<?php

use App\Http\Controllers\Api\v1\HutangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'hutang'
], function () {
    Route::get('/hutang', [HutangController::class, 'hutang']);
    Route::get('/bayar', [HutangController::class, 'bayar']);
    Route::get('/terbayar', [HutangController::class, 'terbayar']);
    // Route::post('/destroy', [HutangController::class, 'destroy']);
});
