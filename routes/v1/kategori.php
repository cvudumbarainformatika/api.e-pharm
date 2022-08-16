<?php

use App\Http\Controllers\Api\v1\KategoriController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'kategori'
], function () {
    Route::get('/index', [KategoriController::class, 'index']);
    Route::post('/store', [KategoriController::class, 'store']);
    Route::post('/destroy', [KategoriController::class, 'destroy']);
});
