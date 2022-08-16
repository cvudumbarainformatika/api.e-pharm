<?php

use App\Http\Controllers\Api\v1\RakController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'rak'
], function () {
    Route::get('/index', [RakController::class, 'index']);
    Route::post('/store', [RakController::class, 'store']);
    Route::post('/destroy', [RakController::class, 'destroy']);
});
