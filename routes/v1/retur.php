<?php

use App\Http\Controllers\Api\v1\ReturController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'retur'
], function () {
    Route::get('/index', [ReturController::class, 'index']);
    Route::get('/all', [ReturController::class, 'returPembelianDanPejualan']);
    Route::get('/pembelian', [ReturController::class, 'returPembelian']);
    Route::get('/penjualan', [ReturController::class, 'returPejualan']);
    // Route::post('/store', [ReturController::class, 'store']);
    // Route::post('/update', [ReturController::class, 'update']);
    // Route::post('/destroy', [ReturController::class, 'destroy']);
});
