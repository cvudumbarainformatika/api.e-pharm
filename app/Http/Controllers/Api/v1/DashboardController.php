<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //peringkat penjualan bulan ini
    public function rank()
    {
        $daily = $this->daylyProductRank();
        $dailyCash = $this->daylyProductCashRank();
        $monthly = $this->monthlyProductRank();
        $monthlyCash = $this->monthlyProductCashRank();
        $lastSevenDays = $this->lastSevenDays();
        $lastSevenDaysCash = $this->lastSevenDaysCash();
        return new JsonResponse([
            'daily' => $daily,
            'dailyCash' => $dailyCash,
            'monthly' => $monthly,
            'monthlyCash' => $monthlyCash,
            'lastSevenDays' => $lastSevenDays,
            'lastSevenDaysCash' => $lastSevenDaysCash,
        ]);
    }
    public function dataProses($data)
    {
        $cl = [];
        foreach ($data as &$key) {
            foreach ($key->details as $value) {
                $value->tanggal = date('d M', strtotime($key->tanggal));
                array_push($cl, $value);
            }
        }
        $col = collect($cl)->groupBy('tanggal');
        $col2 = collect($cl)->groupBy('product_id');
        $chart = [];
        $series_qty = [];
        $series_sub_total = [];
        $chart = [];
        $prod = [];
        foreach ($col as $key => $value) {
            $chart[$key]['min_qty'] = $value->min('qty');
            $chart[$key]['max_qty'] = $value->max('qty');
            $chart[$key]['min_sub_total'] = $value->min('sub_total');
            $chart[$key]['max_sub_total'] = $value->max('sub_total');

            array_push($series_qty, ['x' => $key, 'y' => $value->sum('qty')]);
            array_push($series_sub_total, ['x' => $key, 'y' => $value->sum('sub_total')]);
        }
        foreach ($col2 as $key => $value) {
            array_push($prod, [
                'id' => $key,
                'appear' => $value->count(),
                'sum_qty' => $value->sum('qty'),
                'sum_sub_total' => $value->sum('sub_total')
            ]);
        }
        usort($prod, function ($a, $b) {
            if ($a['sum_sub_total'] == $b['sum_sub_total']) return (0);
            return (($a['sum_sub_total'] > $b['sum_sub_total']) ? -1 : 1);
        });
        return [
            'prod' => $prod,
            'chart' => $chart,
            'series_qty' => [['data' => $series_qty]],
            'series_sub_total' => [['data' => $series_sub_total, 'name' => 'Penjulan']],
        ];
    }
    public function monthlyProductRank()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('status', '>=', 2)
            ->whereMonth('tanggal', date('m'))
            ->oldest('tanggal')
            ->with('details')->get();
        return $this->dataProses($data);
    }

    //peringkat penjualan bulan ini
    public function monthlyProductCashRank()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('status', '>=', 2)
            ->where('jenis', 'tunai')
            ->whereMonth('tanggal', date('m'))
            ->oldest('tanggal')
            ->with('details')->get();
        return $this->dataProses($data);
    }
    //peringkat penjualan hari ini
    public function daylyProductRank()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('status', '>=', 2)
            ->whereDate('tanggal', date('Y-m-d'))
            ->with('details')->get();
        return $this->dataProses($data);
    }

    //peringkat penjualan hari ini
    public function daylyProductCashRank()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('status', '>=', 2)
            ->where('jenis', 'tunai')
            ->whereDate('tanggal', date('Y-m-d'))
            ->with('details')->get();
        return $this->dataProses($data);
    }

    // penjualan last 7 days
    public function lastSevenDays()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->whereDate('tanggal', '>=', date('Y-m-d', strtotime('monday this week')))
            ->whereDate('tanggal', '<', date('Y-m-d', strtotime('monday next week')))
            ->with('details')->get();
        return $this->dataProses($data);
    }

    // penjualan last 7 days tunai
    public function lastSevenDaysCash()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('jenis', 'tunai')
            ->whereDate('tanggal', '>=', date('Y-m-d', strtotime('this week')))
            ->whereDate('tanggal', '<', date('Y-m-d', strtotime('monday next week')))
            ->with('details')->get();
        return $this->dataProses($data);
    }
}
