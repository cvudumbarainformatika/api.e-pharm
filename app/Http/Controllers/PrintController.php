<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaction;
use App\Models\Setting\Info;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function print()
    {
        $invoice = request('invoice');

        $trans = Transaction::with(['kasir', 'detail_transaction.product:id,nama'])->where('reff', $invoice)->first();
        $info = Info::find(1);


        $data = array(
            'invoice' => $invoice,
            'info' => $info,
            'form' => $trans
        );
        return new JsonResponse($data);
        // return view('print.penjualan', $data);
    }
}
