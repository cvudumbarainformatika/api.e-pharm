<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BebanTransaction;
use App\Models\DetailTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $kasir_id = $user->role === 'kasir' ? $user->id : request('kasir_id');
        $super = in_array($user->role, ['root', 'owner']);
        $penjualan = $this->hitung($kasir_id, 'PENJUALAN');
        $retur_penjualan = $this->hitung($kasir_id, 'RETUR PENJUALAN');
        $newpenjualan = $this->newHitung($super, $kasir_id, 'PENJUALAN');
        $newretur_penjualan = $this->newHitung($super, $kasir_id, 'RETUR PENJUALAN');
        $data['penjualan'] = $penjualan;
        $data['newpenjualan'] = $newpenjualan;
        $data['retur_penjualan'] = $retur_penjualan;
        $data['newretur_penjualan'] = $newretur_penjualan;
        $data['super'] = $super;
        // $penjualanNon = $this->hitungNonTunai($kasir_id, 'PENJUALAN');
        // $retur_penjualanNon = $this->hitungNonTunai($kasir_id, 'RETUR PENJUALAN');
        // $pengeluaran = $this->beban($kasir_id, 'PENGELUARAN');
        // $data['penjualanNon'] = $penjualanNon;
        // $data['retur_penjualanNon'] = $retur_penjualanNon;
        // $data['pengeluaran'] = $pengeluaran;
        return new JsonResponse($data);
    }
    public function newHitung($super, $kasir, $nama)
    {
        $data = DetailTransaction::select(
            DB::raw('sum(detail_transactions.sub_total) as uang'),
            'transactions.kasir_id',
            'detail_transactions.transaction_id'

        )
            ->leftJoin('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.status', '>=', 2)
            ->where('transactions.tanggal', 'like', '%' . date('Y-m-d') . '%')
            ->where('transactions.nama', $nama)
            ->where('transactions.status', '>=', 2)
            ->where('transactions.jenis', 'tunai')
            ->when(
                $super,
                function ($q) {
                    $q->groupBy('transactions.kasir_id');
                },
                function ($q) use ($kasir) {
                    $q->where('transactions.kasir_id',  $kasir);
                }
            )
            ->with('transaction:id,kasir_id', 'transaction.kasir:id,name,role')
            ->get();
        return $data;
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
            })
            ->get();

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
            })
            ->get();

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
            })
            ->get();

        return $data;
    }
}
