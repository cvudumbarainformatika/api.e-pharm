<?php

use App\Http\Controllers\Api\v1\KasirController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'kasir'
], function () {
    Route::get('/index', [KasirController::class, 'index']);
});
