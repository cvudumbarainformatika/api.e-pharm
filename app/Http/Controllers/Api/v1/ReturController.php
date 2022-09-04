<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ReturController extends Controller
{
    public function index()
    {
        $data = Transaction::where(['status' => 1])
            ->orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->get();

        return TransactionResource::collection($data);
    }
    public function returPembelianDanPejualan()
    {
        $data = Transaction::where('status', 1)
            ->whereIn('nama', ['PEMBELIAN', 'PENJUALAN'])
            // ->orWhere('nama', request('nama2'))
            ->with(['kasir', 'supplier.perusahaan', 'customer', 'dokter'])
            ->latest()->filter(request(['q']))->limit(20)->get();

        return TransactionResource::collection($data);
    }
}
