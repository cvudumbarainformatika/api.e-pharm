<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index()
    {
        // $data = Transaction::paginate();
        $data = Transaction::orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->latest()->paginate(request('per_page'));
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function namaStatus()
    {
        $data = Transaction::where(['nama' => request('nama'), 'status' => request('status')])
            ->orderBy(request()->order_by, request()->sort)
            ->latest()->paginate(request('per_page'));
        return TransactionResource::collection($data);
    }
    public function nama()
    {
        $data = Transaction::where(['nama' => request('nama')])
            ->orderBy(request()->order_by, request()->sort)
            ->latest()->paginate(request('per_page'));
        return TransactionResource::collection($data);
    }
    public function search()
    {
        $data = Transaction::filter(request(['q']))->latest()->get();
        return TransactionResource::collection($data);
    }
}
