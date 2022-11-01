<?php

use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\HutangController;
use App\Http\Controllers\Api\v1\TagihanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'dashboard'
], function () {
    Route::get('/hutang', [HutangController::class, 'hutang']);
    Route::get('/tagihan', [TagihanController::class, 'tagihan']);
    Route::get('/rank', [DashboardController::class, 'rank']); //peringkat
});
