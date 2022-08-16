<?php

use App\Http\Controllers\Api\v1\KategoriController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'kategori'
], function () {
    Route::get('/index', [KategoriController::class, 'index']);
    Route::get('/store', [KategoriController::class, 'store']);
    Route::get('/destroy', [KategoriController::class, 'destroy']);
});
