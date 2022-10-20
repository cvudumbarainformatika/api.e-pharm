<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\v1\SettingController;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
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
        $menu =  [
            ["name" => "dashboard", "icon" => "icon-mat-dashboard", "link" => "dashboard"],
            ["name" => "master", "icon" => "icon-mat-dataset", "link" => "master"],
            ["name" => "transaksi", "icon" => "icon-mat-sync_alt", "link" => "transaksi"],
            ["name" => "history", "icon" => "icon-mat-history", "link" => "history"],
            ["name" => "laporan", "icon" => "icon-mat-description", "link" => "laporan"],
            ["name" => "setting", "icon" => "icon-mat-settings", "link" => "setting"]
        ];
        $nama = 'eAchy';
        $masuk = ['nama' => $nama, 'menu' => $menu];
        // return new JsonResponse($masuk['nama']);
        $data = SettingController::simpanMenu($masuk);
        return new JsonResponse($data);

        // $q = Transaction::query()->where('status', '=', 1);
        // $this->until($q, 'range', '2022-09-22', '2022-09-24');
        // $q->whereHas('detail_transaction', function ($m) {
        //     $m->where('product_id', '=', 2);
        // });
        // $data = $q->with('detail_transaction')->paginate(15);
        // $masuk = DetailTransaction::all();
        // ->with('transaction', 'product');

        // $data = collect($masuk)->except(['created_at', 'updated_at', 'uuid', 'id']);
        // $grup = $data->only(['created_at', 'updated_at', 'uuid', 'id']);
        // return $grup->all();
        // return $data;
    }

    public function getSingleDetails($header, $nama)
    {
        $before = DetailTransaction::where('product_id', $header->product_id)
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 2)
                    ->whereDate('tanggal', '<', $header->from);
            })->get();
        $period = DetailTransaction::where('product_id', $header->product_id)
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 2)
                    ->whereDate('tanggal', '=', $header->from);
            })->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );
        return $data;
    }
    public function cari()
    {
        // $q = Transaction::filter(['product'])->with('detail_transaction.product');
        // $data = $q->get();
        // return new JsonResponse($data);
        // $data = DetailTransaction::where('product_id', 1)
        //     ->whereHas('transaction', function ($f) {
        //         $f->where('nama', '=', 'PENJUALAN')
        //             ->where('status', '=', 2)
        //             ->whereDate('tanggal', '<', '2022-10-16');
        //     })
        //     ->get();
        // $colle = collect($data)->sum('qty');
        // return new JsonResponse($colle);

        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => 2
        );
        $stokMasuk = $this->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $this->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $this->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $this->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $this->getSingleDetails($header, 'FORM PENYESUAIAN');

        $produk = Product::where('id', $header->product_id)->first();

        $masukBefore = collect($stokMasuk->before)->sum('qty');
        $masukPeriod = collect($stokMasuk->period)->sum('qty');
        $keluarBefore = collect($stokKeluar->before)->sum('qty');
        $keluarPeriod = collect($stokKeluar->period)->sum('qty');
        $retBeliBefore = collect($returPembelian->before)->sum('qty');
        $retBeliPeriod = collect($returPembelian->period)->sum('qty');
        $retJualBefore = collect($returPenjualan->before)->sum('qty');
        $retJualPeriod = collect($returPenjualan->period)->sum('qty');
        $penyeBefore = collect($penyesuaian->before)->sum('qty');
        $penyePeriod = collect($penyesuaian->period)->sum('qty');

        $sebelum = $masukBefore - $keluarBefore + $retJualBefore - $retBeliBefore + $penyeBefore;
        $berjalan = $masukPeriod - $keluarPeriod + $retJualPeriod - $retBeliPeriod + $penyePeriod;
        $awal = $produk->stok_awal + $sebelum;
        $sekarang = $awal + $berjalan;
        $produk->stok_awal = $awal;
        $produk->stokSekarang = $sekarang;
        $produk->stokBerjalan = $berjalan;

        $data = (object) array(
            'produk' => $produk,
        );

        return new JsonResponse($data);
    }

    public function retur()
    {
        $today = date('Y-m-d H:i:s');
        $before = date('Y-m-d', strtotime('-7 days'));
        $carbon = Carbon::now()->locale('id_ID');
        $nama = 'PENJUALAN';
        $data = Transaction::where('status', 2)->filter(request(['q']))
            ->whereIn('nama', ['PEMBELIAN', 'PENJUALAN'])
            // ->orWhere('nama', request('nama2'))
            ->whereDate('tanggal', '>=', $before)
            ->whereDate('tanggal', '<=', $today)
            ->with(['kasir', 'supplier.perusahaan', 'customer', 'dokter'])
            ->latest()->limit(20)->get();
        $jumlah = collect($data)->count();
        $nama = collect($data)->groupBy('nama')->count();
        $tanggal = collect($data)->groupBy('nama');
        return new JsonResponse([
            'hari ini ' => $today,
            'carbon ' => $carbon,
            '7 hari yll' => $before,
            'jumlah' => $jumlah,
            'nama' => $nama,
            'tanggal' => $tanggal,
            'data' => $data
        ]);
    }
}
