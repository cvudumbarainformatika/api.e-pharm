<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BebanTransaction;
use App\Models\DetailTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KasirController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $kasir_id = $user->role === 'kasir' ? $user->id : request('kasir_id');
        $penjualan = $this->hitung($kasir_id, 'PENJUALAN');
        $penjualanNon = $this->hitungNonTunai($kasir_id, 'PENJUALAN');
        $retur_penjualan = $this->hitung($kasir_id, 'RETUR PENJUALAN');
        $retur_penjualanNon = $this->hitungNonTunai($kasir_id, 'RETUR PENJUALAN');
        $pengeluaran = $this->beban($kasir_id, 'PENGELUARAN');
        $data['penjualan'] = $penjualan;
        $data['penjualanNon'] = $penjualanNon;
        $data['retur_penjualan'] = $retur_penjualan;
        $data['retur_penjualanNon'] = $retur_penjualanNon;
        $data['pengeluaran'] = $pengeluaran;
        return new JsonResponse($data);
    }
    public function hitung($kasir, $nama)
    {
        $data = DetailTransaction::selectRaw('sum(sub_total) as uang')
            ->whereHas('transaction', function ($f) use ($kasir, $nama) {
                $f->where('status', '>=', 2)
                    ->whereDate('tanggal', date('Y-m-d'))
                    ->where('nama', $nama)
                    ->where('status', '>=', 2)
                    ->where('jenis', 'tunai')
                    ->where('kasir_id',  $kasir);
            })->get();

        return $data;
    }
    public function hitungNonTunai($kasir, $nama)
    {
        $data = DetailTransaction::selectRaw('sum(sub_total) as uang')
            ->whereHas('transaction', function ($f) use ($kasir, $nama) {
                $f->where('status', '>=', 2)
                    ->whereDate('tanggal', date('Y-m-d'))
                    ->where('nama', $nama)
                    ->where('status', '>=', 2)
                    ->where('jenis', 'non-tunai')
                    ->where('kasir_id',  $kasir);
            })->get();

        return $data;
    }
    public function beban($kasir, $nama)
    {
        $data = BebanTransaction::selectRaw('sum(sub_total) as beban')
            ->whereHas('transaction', function ($f) use ($kasir, $nama) {
                $f->where('status', '>=', 2)
                    ->whereDate('tanggal', date('Y-m-d'))
                    ->where('nama', $nama)
                    ->where('kasir_id',  $kasir);
            })->get();

        return $data;
    }
}
