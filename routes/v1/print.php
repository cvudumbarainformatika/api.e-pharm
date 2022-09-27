<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'print'
], function () {
    Route::get('/print', [PrintController::class, 'print']);
});
