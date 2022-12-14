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
        $data = Transaction::where(['status' => 2])
            ->orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->get();

        return TransactionResource::collection($data);
    }
    public function returPembelianDanPejualan()
    {
        $data = Transaction::where('status', 2)
            ->whereIn('nama', ['PEMBELIAN', 'PENJUALAN'])
            // ->orWhere('nama', request('nama2'))
            ->with(['kasir', 'supplier.perusahaan', 'customer', 'dokter'])
            ->latest()->filter(request(['q']))->limit(20)->get();

        return TransactionResource::collection($data);
    }
    public function returPejualan()
    {
        $today = date('Y-m-d');
        $before = date('Y-m-d', strtotime('-7 days'));
        $data = Transaction::where('status', 2)
            ->where('nama', '=', 'PENJUALAN')
            ->whereDate('tanggal', '>=', $before)
            ->whereDate('tanggal', '<=', $today)
            ->with(['kasir', 'supplier.perusahaan', 'customer', 'dokter'])
            ->latest()->filter(request(['q']))->limit(20)->get();

        return TransactionResource::collection($data);
    }
    public function returPembelian()
    {
        $data = Transaction::where('status', 2)
            ->where('nama', '=', 'PEMBELIAN')
            ->with(['kasir', 'supplier.perusahaan', 'customer', 'dokter'])
            ->latest()->filter(request(['q']))->limit(20)->get();

        return TransactionResource::collection($data);
    }
}
