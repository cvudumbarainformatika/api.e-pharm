<?php

use App\Http\Controllers\Api\v1\DokterController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'dokter'
], function () {
    Route::get('/index', [DokterController::class, 'index']);
    Route::post('/store', [DokterController::class, 'store']);
    Route::post('/destroy', [DokterController::class, 'destroy']);
});
