<?php

use App\Http\Controllers\Api\v1\MerkController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'merk'
], function () {
    Route::get('/index', [MerkController::class, 'index']);
    Route::post('/store', [MerkController::class, 'store']);
    Route::post('/destroy', [MerkController::class, 'destroy']);
});
