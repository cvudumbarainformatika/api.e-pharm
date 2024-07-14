<?php

use App\Http\Controllers\Api\v1\DistribusiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'distribusi/'
], function () {
    Route::get('get-nodist-draft', [DistribusiController::class, 'getNodistDraft']);
    Route::get('list', [DistribusiController::class, 'getList']);
    Route::post('simpan', [DistribusiController::class, 'store']);
    Route::post('hapus-draft', [DistribusiController::class, 'daleteItem']);
    Route::post('selesai', [DistribusiController::class, 'selesai']);
});
