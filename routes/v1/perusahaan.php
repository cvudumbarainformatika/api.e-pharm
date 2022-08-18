<?php

use App\Http\Controllers\Api\v1\PerusahaanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'perusahaan'
], function () {
    Route::get('/index', [PerusahaanController::class, 'index']);
    Route::post('/store', [PerusahaanController::class, 'store']);
    Route::post('/destroy', [PerusahaanController::class, 'destroy']);
});
