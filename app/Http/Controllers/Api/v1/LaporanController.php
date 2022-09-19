<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BebanTransaction;
use App\Models\DetailTransaction;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function periode($query, $date, $hari, $bulan, $to, $from)
    {
        if ($date === 'hari') {
            if (request()->has('hari') && $hari !== null) {
                $query->whereDay('tanggal', '=', $hari);
            } else {
                $query->whereDay('tanggal', '=', date('d'));
            }
        } else if ($date === 'bulan') {
            if (request()->has('bulan') && $bulan !== null) {
                $query->whereMonth('tanggal', '=', $bulan);
            } else {
                $query->whereMonth('tanggal', '=', date('m'));
            }
        } else if ($date === 'spesifik') {
            $query->whereDate('tanggal', '=', $from);
        } else {
            $query->whereBetween('tanggal', [$from, $to]);
        }
    }

    public function getHutangSupplier()
    {
        $query = DetailTransaction::query()->selectRaw('product_id, harga, sum(qty) as jml');
        $query->whereHas('transaction', function ($gg) {
            // $gg->where(['nama' => 'PENJUALAN', 'status' => 1, 'jenis' => 'hutang', 'supplier_id' => request('supplier_id')])
            $gg->where('nama', '=', 'PEMBELIAN')
                ->where('status', '=', 1)
                ->where('jenis', '=', 'hutang')
                ->where('supplier_id', '=', request('supplier_id'))
                ->whereDate('tanggal', '<=', date('Y-m-d'));
        });
        $hutang = $query->groupBy('product_id', 'harga')
            // ->with(['product'])
            ->get();
        $dibayar = BebanTransaction::selectRaw('beban_id, sum(sub_total) as total')->whereHas('transaction', function ($apem) {
            $apem->where(['nama' => 'BEBAN', 'status' => 1, 'supplier_id' => request('supplier_id')])
                ->whereDate('tanggal', '<=', date('Y-m-d'));
        })
            // ->groupBy('beban_id')
            ->with('beban')->get();
        $supplier = Supplier::find(request('supplier_id'));


        return new JsonResponse(['hutang' => $hutang, 'dibayar' => $dibayar, 'awal' => $supplier->saldo_awal_hutang]);
    }
}
