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
    public function pembelian()
    {
        $data = Transaction::where(['nama' => 'PEMBELIAN', 'status' => 1])
            ->with(['kasir', 'supplier.perusahaan'])
            ->orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->limit(10)->get();

        return TransactionResource::collection($data);
    }
}
