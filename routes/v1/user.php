<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::get('/profile', [AuthController::class, 'userProfile']);
    Route::get('/all', [AuthController::class, 'userAll']);
    Route::get('/kasir', [AuthController::class, 'userKasir']);
});
