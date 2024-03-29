<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HutangController extends Controller
{
    public function hutang()
    {
        $data = Transaction::where('nama', 'PEMBELIAN')
            ->where('jenis', 'hutang')
            ->where('status', '=', 2)
            ->with('supplier', 'detail_transaction')
            ->oldest('tempo')
            ->get();

        return new JsonResponse($data);
    }
    public function terbayar()
    {
        $data = Transaction::where('nama', 'PEMBELIAN')
            ->where('jenis', 'hutang')
            ->whereBetween('tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('status', '>', 2)
            ->with('supplier', 'detail_transaction.product')
            ->latest('updated_at')
            ->get();

        return new JsonResponse($data);
    }
    public function bayar()
    {

        $bayar = Transaction::where('nama', 'PENGELUARAN')
            ->where('supplier_id', '<>', null)
            ->whereBetween('tanggal_bayar', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('status', '=', 2)
            ->with('supplier', 'kasir', 'beban_transaction.beban')
            ->latest('tanggal_bayar')
            ->get();
        return new JsonResponse($bayar);
    }
    public static function statusPembelian($header)
    {
        $data = Transaction::where('reff', $header->pbreff)->first();
        $data->status = 3;
        $data->tanggal_bayar = $header->tanggal_bayar;
        if (!$data->save()) {
            return new JsonResponse(['message' => 'gagal'], 500);
        }
        return new JsonResponse(['message' => 'success'], 200);
    }
}
