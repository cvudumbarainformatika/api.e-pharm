<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaction;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function print()
    {
        $invoice = request('invoice');
        $bayar = request('bayar');
        $kembali = request('kembali');

        $trans = Transaction::with(['kasir', 'detail_transaction.product'])->where('reff', $invoice)->first();


        $data = array(
            'invoice' => $invoice,
            'bayar' => $bayar,
            'kembali' => $kembali,
            'tanggal' => $trans->created_at,
            'petugas' => $trans->kasir->name,
            'details' => $trans->detail_transaction,
            'total' => DetailTransaction::where('transaction_id', $trans->id)->sum('sub_total')
        );
        // return new JsonResponse($data);
        return view('print.penjualan', $data);
    }
}
