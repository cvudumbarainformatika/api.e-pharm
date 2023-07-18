<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\v1\SettingController;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
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
        // $menu =  [
        //     ["name" => "dashboard", "icon" => "icon-mat-dashboard", "link" => "dashboard"],
        //     ["name" => "master", "icon" => "icon-mat-dataset", "link" => "master"],
        //     ["name" => "transaksi", "icon" => "icon-mat-sync_alt", "link" => "transaksi"],
        //     ["name" => "history", "icon" => "icon-mat-history", "link" => "history"],
        //     ["name" => "laporan", "icon" => "icon-mat-description", "link" => "laporan"],
        //     ["name" => "setting", "icon" => "icon-mat-settings", "link" => "setting"]
        // ];
        // $nama = 'eAchy';
        // $masuk = ['nama' => $nama, 'menu' => $menu];
        // return new JsonResponse($masuk['nama']);
        // $data = SettingController::simpanMenu($masuk);
        // return new JsonResponse($data);

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

        // ganti password berhasil ini...
        // $user = User::where('name', 'root')->first();
        // $user->password = bcrypt('sekarep12345');
        // $user->save();

        // $data = Transaction::where('reff', 'PBL-lk2tmn2womq6o')->first();
        // $apem = DetailTransaction::where('transaction_id', $data->id)->get();
        // // $qty = $apem[1][0]->qty;
        // foreach ($apem as $key) {
        //     $prod = Product::find($key['product_id']);
        //     $harga = 0;
        //     $hargaPpn = 0;

        //     $discPerItem = $prod->harga_beli  * (5 / 100);
        //     $harga =  $prod->harga_beli - $discPerItem;

        //     $ppnPerItem = $harga  * (11 / 100);
        //     $hargaPpn = $harga + $ppnPerItem;

        //     $harg = ceil($hargaPpn);
        //     $selisi = ceil($harg - $prod->harga_beli);
        //     $selisih = $selisi <= 0 ? 0 : $selisi; //ceil($selisi);
        //     return new JsonResponse([
        //         'harg' => $harg,
        //         'hargaPpn' => $hargaPpn,
        //         'harga' => $harga,
        //         'selisi' => $selisi,
        //         'selisih' => $selisih,
        //         'prod' => $prod,
        //         'data' => $data,
        //         'apem' => $apem,
        //     ]);
        // }

        // return new JsonResponse([
        //     'data' => $data,
        //     'apem' => $apem,
        // ]);
        return new JsonResponse([
            'date' => date('Y-m-t'),
            'date2' => date('Y-m-01')
        ]);
    }

    public function getSingleDetails($header, $nama)
    {
        $before = DetailTransaction::where('product_id', $header->product_id)
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '>=', 2)
                    ->whereDate('tanggal', '<', $header->from);
            })->get();
        $period = DetailTransaction::where('product_id', $header->product_id)
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '>=', 2)
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
            'product_id' => 1
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
    public function dashboard()
    {
        // penjualan last 7 days
        // $data = Transaction::where('nama', 'PENJUALAN')
        //     ->where('jenis', 'tunai')
        //     ->whereDate('tanggal', '<=', date('Y-m-d'))
        //     ->whereDate('tanggal', '>=', date('Y-m-d', strtotime('-7 days')))
        //     ->with('details')->get();
        // $apem = collect($data)->groupBy('tanggal');
        // $cl = [];
        // $tg = [];
        // foreach ($apem as $a => $value) {
        //     foreach ($value as $b) {
        //         foreach ($b->details as $c) {
        //             $t = new DateTime($a);
        //             array_push($tg, [
        //                 'tgl' => $a,
        //                 'product_id' => $c->product_id,
        //                 'qty' => $c->qty,
        //                 'sub_total' => $c->sub_total,
        //             ]);
        //         }
        //     }
        // }
        // $col = collect($tg)->groupBy('product_id');
        // $tgl = collect($tg);
        // $prod = [];
        // $sum = [];
        // foreach ($col as $key => $value) {

        //     array_push($prod, [
        //         'id' => $key,
        //         'appear' => $value->count(),
        //         'sum_qty' => $value->sum('qty'),
        //         'total' => $value->sum('sub_total'),
        //     ]);
        //     array_push($sum, ['id' => $key, 'value' => $value->sum('sub_total')]);
        // $prod[$key] = $value->count();
        // $sum[$key] = $value->sum('sub_total');
        // }
        // usort($prod, function ($a, $b) {
        //     if ($a['value'] == $b['value']) return (0);
        //     return (($a['value'] > $b['value']) ? -1 : 1);
        // });
        // usort($sum, function ($a, $b) {
        //     if ($a['value'] == $b['value']) return (0);
        //     return (($a['value'] > $b['value']) ? -1 : 1);
        // });

        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('status', '>=', 2)
            ->whereMonth('tanggal', date('m'))
            ->with('details')->get();
        $cl = [];
        foreach ($data as &$key) {
            foreach ($key->details as $value) {
                $value->tanggal = date('d-m-Y', strtotime($key->tanggal));
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
        $miqty = [];
        $maxqty = [];
        $misub = [];
        $maxsub = [];
        foreach ($col as $key => $value) {
            $chart[$key]['min_qty'] = $value->min('qty');
            $chart[$key]['max_qty'] = $value->max('qty');
            $chart[$key]['min_sub_total'] = $value->min('sub_total');
            $chart[$key]['max_sub_total'] = $value->max('sub_total');

            // array_push($tgl, $key);
            array_push($series_qty, [$key, $value->sum('qty')]);
            array_push($series_sub_total, [$key, $value->sum('sub_total')]);
        }
        foreach ($col2 as $key => $value) {

            array_push($miqty, $value->min('qty'));
            array_push($maxqty, $value->max('qty'));
            array_push($misub, $value->min('sub_total'));
            array_push($maxsub, $value->max('sub_total'));
            array_push($prod, ['id' => $key, 'appear' => $value->count(), 'sum_qty' => $value->sum('qty')]);
        }
        $mimaqty = ['min_qty' => $data[0]->details->min('qty'), 'max_qty' => $data[0]->details->max('qty')];
        $mimasub_total = ['min_sub_total' => $data[0]->details->min('sub_total'), 'max_sub_total' => $data[0]->details->max('sub_total')];
        usort($prod, function ($a, $b) {
            if ($a['sum_qty'] == $b['sum_qty']) return (0);
            return (($a['sum_qty'] > $b['sum_qty']) ? -1 : 1);
        });
        $a = collect($series_qty);
        $b = ['data' => $a];
        $c = (object) $b;
        $week = date('Y-m-d', strtotime('monday this week'));
        $week2 = date('Y-m-d', strtotime('monday next week'));
        return new JsonResponse([
            'week' => $week,
            'week 2' => $week2,
            // 'series_qty' => $c,
            // 'data' => $data,
            // 'mimaqty' => $mimaqty,
            // 'mimasub_total' => $mimasub_total,
            // // 'apem' => $apem,
            // // 'col2' => $col2,
            // 'chart' => $chart,
            // 'series_sub_total' => $series_sub_total,
            // 'prod' => $prod,
            // // 'tgl' => $tgl,
            // 'col' => $col,
            // 'cl' => $cl,
            // 'data' => $data,
            // 'tg' => $tg,
        ]);
    }

    public function wawan()
    {

        $data = 'PNSDA-apem';
        $return = explode('-', $data);
        return new JsonResponse($return[0]);
    }
}
