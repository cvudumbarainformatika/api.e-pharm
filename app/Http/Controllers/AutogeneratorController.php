<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AutogeneratorController extends Controller
{
    //
    public function index()
    {
        $table = 'transactions';
        $data = Schema::getColumnListing($table);


        echo '<br>';
        foreach ($data as $key) {
            echo '\'' . $key . '\' => $this->' . $key . ',<br>';
        }
        echo '<br>';
    }
    public function until($query, $selection, $from, $to)
    {
        if ($selection === 'tillToday') {
            $query->whereDate('tanggal', '<=', date('Y-m-d'));
        } else if ($selection === 'spesifik') {
            $query->whereDate('tanggal', '<=', $from);
        } else if ($selection === 'range') {
            $query->whereDate('tanggal', '>=', $from)->whereDate('tanggal', '<=', $to);
        }
    }
    public function coba()
    {
        $q = Transaction::query()->where('status', '=', 1);
        $this->until($q, 'range', '2022-09-22', '2022-09-24');
        $q->whereHas('detail_transaction', function ($m) {
            $m->where('product_id', '=', 2);
        });
        $data = $q->with('detail_transaction')->paginate(15);
        return new JsonResponse($data);
    }

    public function cari()
    {
        $q = Transaction::filter(['product'])->with('detail_transaction.product');
        $data = $q->get();
        return new JsonResponse($data);
    }
}
