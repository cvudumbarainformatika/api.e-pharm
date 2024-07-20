<?php

use App\Http\Controllers\Api\v1\HutangController;
use App\Http\Controllers\Api\v1\LaporanController;
use App\Http\Controllers\Api\v1\TagihanController;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::view("login", "login");
Route::view("print.penjualan", "print");
Route::get('/autogen', [AutogeneratorController::class, 'index']);
Route::get('/coba', [AutogeneratorController::class, 'coba']);
// Route::get('/cari', [AutogeneratorController::class, 'cari']);
// Route::get('/retur', [AutogeneratorController::class, 'retur']);
Route::get('/print', [PrintController::class, 'print']);
// Route::get('/hutang', [HutangController::class, 'bayar']);
// Route::get('/piutang', [TagihanController::class, 'piutang']);
// Route::get('/terbayar', [TagihanController::class, 'transaksiTerbayar']);
// Route::get('/dash', [AutogeneratorController::class, 'dashboard']);
Route::get('/kode', [AutogeneratorController::class, 'setKode']);
Route::get('/wawan', [AutogeneratorController::class, 'wawan']);
Route::get('/det', [AutogeneratorController::class, 'getSingleDetails']);
Route::get('/stok', [AutogeneratorController::class, 'getStokProd']);
Route::get('/anu', [AutogeneratorController::class, 'anuGet']);
// Route::get('/time', function () {
//     echo time();
// });
// Route::get('/get-stok', [LaporanController::class, 'ambilStok']);
