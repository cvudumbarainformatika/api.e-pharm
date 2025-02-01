<?php

use App\Http\Controllers\Api\v1\OpnameController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'opname'
], function () {
    Route::get('/list', [OpnameController::class, 'getOpname']);
    Route::post('/store', [OpnameController::class, 'store']);
});
