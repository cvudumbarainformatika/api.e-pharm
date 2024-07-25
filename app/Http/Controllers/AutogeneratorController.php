<?php

namespace App\Http\Controllers;

use App\Helpers\NumberHelper;
use App\Http\Controllers\Api\v1\CloudReportController;
use App\Http\Controllers\Api\v1\LaporanBaruController;
use App\Http\Controllers\Api\v1\SettingController;
use App\Models\Beban;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\DetailTransaction;
use App\Models\DistribusiAntarToko;
use App\Models\Dokter;
use App\Models\Kategori;
use App\Models\Merk;
use App\Models\Perusahaan;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\SatuanBesar;
use App\Models\Setting\Info;
use App\Models\Supplier;
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
    public function report()
    {
        $head = (object) [
            'periode' => 'bulan', // bulan atau range,, kosong berarti hari ini tergantung from dan to
            'from' => '2024-07-01',
            // 'to' => '2024-07-30',
        ];
        $data = CloudReportController::report($head);
        return $data;
    }

    public function setHarga()
    {
        $trans = DistribusiAntarToko::where('harga', '=', 0)->get();
        $tr = DistribusiAntarToko::select('kode_produk')->where('harga', '=', 0)->distinct()->pluck('kode_produk');
        $prod = Product::select('id', 'kode_produk', 'harga_beli')->whereIn('kode_produk', $tr)->get();
        $prdArr = $prod->toArray();
        foreach ($trans as $key) {
            $str = $key['kode_produk'];
            $clm = array_column($prdArr, 'kode_produk');
            $ind = array_search($str, $clm);
            // return $ind;
            if ($ind >= 0) {
                $sub = $prod[$ind]->harga_beli * $key['qty'];
                $key->update([
                    'harga' => $prod[$ind]['harga_beli'],
                    'subtotal' => $sub,
                ]);
                // return $prod[$ind];
            }
        }
        return new JsonResponse([
            'tr' => $tr,
            'trans' => $trans,
            'prod    ' => $prod,
        ]);
    }
    public function anuGet()
    {
        $data = "{\"message\":{\"id\":8,\"sender\":\"APS0001\",\"receiver\":\"APS0002\",\"type\":\"kirim permintaan distribusi\",\"model\":\"HeaderDistribusi\",\"content\":{\"id\":1,\"nodistribusi\":\"1307202400001\",\"pengirim\":\"root\",\"dari\":\"APS0001\",\"tujuan\":\"APS0002\",\"penerima\":\"gudang\",\"tgl_permintaan\":null,\"tgl_distribusi\":\"2024-07-18\",\"tgl_terima\":null,\"status\":2,\"created_at\":\"2024-07-15T13:59:03.000000Z\",\"updated_at\":\"2024-07-19T18:32:17.000000Z\",\"details\":[{\"id\":1,\"nodistribusi\":\"1307202400001\",\"product_id\":2419,\"kode_produk\":\"PRD02419\",\"jumlah\":10,\"qty\":20,\"harga\":0,\"subtotal\":0,\"expired\":\"2026-07-31\",\"created_at\":\"2024-07-15T13:59:03.000000Z\",\"updated_at\":\"2024-07-18T15:43:58.000000Z\"},{\"id\":2,\"nodistribusi\":\"1307202400001\",\"product_id\":1410,\"kode_produk\":\"PRD01410\",\"jumlah\":10,\"qty\":10,\"harga\":0,\"subtotal\":0,\"expired\":\"2026-07-31\",\"created_at\":\"2024-07-15T13:59:03.000000Z\",\"updated_at\":\"2024-07-18T15:43:58.000000Z\"}]},\"is_read\":0,\"created_at\":\"2024-07-19T18:32:17.000000Z\",\"updated_at\":\"2024-07-19T18:32:17.000000Z\"}}";

        return json_decode($data, true);
    }
    public function index()
    {
        // $table = 'transactions';
        // $data = Schema::getColumnListing($table);


        // echo '<br>';
        // foreach ($data as $key) {
        //     echo '\'' . $key . '\' => $this->' . $key . ',<br>';
        // }
        // echo '<br>';

        $info = Info::first();
        // $rw = str_split($info->kodecabang);
        // $hlf = [];
        // foreach ($rw as $key) {
        //     if (!is_numeric($key)) $hlf[] =  $key;
        //     else if (is_numeric($key) && (int)$key != 0) $hlf[] =  $key;
        // }
        // $kodecabang = join('', $hlf);
        // return $kodecabang;

        // $cabang = Cabang::pluck('kodecabang')->toArray();
        // $ind = array_search($info->kodecabang, $cabang);
        // $anu = $cabang;
        // unset($anu[$ind]);
        // return [
        //     'cabang' => $cabang,
        //     'kode' => $info->kodecabang,
        //     'ind' => $ind,
        //     'anu' => $anu,
        // ];
        $model = [
            [
                'name' => Beban::class,
                'sring' => 'Beban',
            ],
            [
                'name' => Cabang::class,
                'sring' => 'Cabang',
            ],
            [
                'name' => Customer::class,
                'sring' => 'Customer',
            ],
            [
                'name' => Dokter::class,
                'sring' => 'Dokter',
            ],
            [
                'name' => Satuan::class,
                'sring' => 'Satuan',
            ],
        ];
        $str = 'Satuan';
        $keys = array_column($model, 'sring');
        $ind = array_search($str, $keys);
        $data = $model[$ind]['name']::get();
        return [
            'str' => $str,
            'keys' => $keys,
            'ind' => $ind,
            'model' => $model[$ind]['name'],
            'data' => $data,
        ];
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

    public function getStokProd()
    {
        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => 93
        );
        $produ = Product::find($header->product_id);
        $singleDet = new LaporanBaruController;
        $stokMasuk = $singleDet->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $singleDet->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $singleDet->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $singleDet->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $singleDet->getSingleDetails($header, 'FORM PENYESUAIAN');

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
        $awal = $produ->stok_awal + $sebelum;
        $sekarang = $awal + $berjalan;

        return [
            'masukBefore' => $masukBefore,
            'masukPeriod' => $masukPeriod,
            'keluarBefore' => $keluarBefore,
            'keluarPeriod' => $keluarPeriod,
            'retBeliBefore' => $retBeliBefore,
            'retBeliPeriod' => $retBeliPeriod,
            'retJualBefore' => $retJualBefore,
            'retJualPeriod' => $retJualPeriod,
            'penyeBefore' => $penyeBefore,
            'penyePeriod' => $penyePeriod,
            'sebelum' => $sebelum,
            'berjalan' => $berjalan,
            'awal' => $awal,
            'sekarang' => $sekarang,
        ];
    }
    public function getSingleDetails()
    {
        $header = (object)[];
        $nama = 'PENJUALAN';
        $header->product_id = 93;
        $header->from = '2023-11-01';
        $header->to = '2023-11-09 ';

        $before = Transaction::select(

            'detail_transactions.qty'
        )->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $header->from)
            ->get();
        $period = Transaction::select(

            'detail_transactions.qty'
        )->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '=', $header->from)
            ->get();

        $before1 = DetailTransaction::select(

            'detail_transactions.qty'
        )->leftJoin('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $header->from)
            ->get();
        $period1 = DetailTransaction::select(

            'detail_transactions.qty'
        )->leftJoin('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '=', $header->from)
            ->get();


        $data = (object) array(
            'before1' => $before1,
            'period1' => $period1,
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

    public function setKode()
    {

        $beban = Beban::whereNull('kode_beban')->get();
        $customer = Customer::whereNull('kode_customer')->get();
        $dokter = Dokter::whereNull('kode_dokter')->get();
        $kategory = Kategori::whereNull('kode_kategory')->get();
        $merk = Merk::whereNull('kode_merk')->get();
        $satuanBesar = SatuanBesar::whereNull('kode_satuan')->get();
        $satuan = Satuan::whereNull('kode_satuan')->get();
        $suplier = Supplier::whereNull('kode_supplier')->get();
        $produk = Product::whereNull('kode_produk')->get();
        $perusahaan = Perusahaan::whereNull('kode')->get();

        if (count($beban)) {
            foreach ($beban as $key) {
                $kode = NumberHelper::setNumber($key->id, 'BBN');
                $key->update([
                    'kode_beban' => $kode
                ]);
            }
        }
        if (count($customer)) {
            foreach ($customer as $key) {
                $kode = NumberHelper::setNumber($key->id, 'CST');
                $key->update([
                    'kode_customer' => $kode
                ]);
            }
        }
        if (count($dokter)) {
            foreach ($dokter as $key) {
                $kode = NumberHelper::setNumber($key->id, 'DKT');
                $key->update([
                    'kode_dokter' => $kode
                ]);
            }
        }
        if (count($kategory)) {
            foreach ($kategory as $key) {
                $kode = NumberHelper::setNumber($key->id, 'KTR');
                $key->update([
                    'kode_kategory' => $kode
                ]);
            }
        }
        if (count($merk)) {
            foreach ($merk as $key) {
                $kode = NumberHelper::setNumber($key->id, 'MRK');
                $key->update([
                    'kode_merk' => $kode
                ]);
            }
        }
        if (count($satuanBesar)) {
            foreach ($satuanBesar as $key) {
                $kode = NumberHelper::setNumber($key->id, 'STB');
                $key->update([
                    'kode_satuan' => $kode
                ]);
            }
        }
        if (count($satuan)) {
            foreach ($satuan as $key) {
                $kode = NumberHelper::setNumber($key->id, 'STK');
                $key->update([
                    'kode_satuan' => $kode
                ]);
            }
        }
        if (count($suplier)) {
            foreach ($suplier as $key) {
                $kode = NumberHelper::setNumber($key->id, 'SUP');
                $key->update([
                    'kode_supplier' => $kode
                ]);
            }
        }
        if (count($produk)) {
            foreach ($produk as $key) {
                $kode = NumberHelper::setNumber($key->id, 'PRD');
                $key->update([
                    'kode_produk' => $kode
                ]);
            }
        }
        if (count($perusahaan)) {
            foreach ($perusahaan as $key) {
                $kode = NumberHelper::setNumber($key->id, 'CMP');
                $key->update([
                    'kode' => $kode
                ]);
            }
        }

        return new JsonResponse([
            'beban' => $beban,
            'customer' => $customer,
            'dokter' => $dokter,
            'kategory' => $kategory,
            'merk' => $merk,
            'satuanBesar' => $satuanBesar,
            'satuan' => $satuan,
            'suplier' => $suplier,
            'produk' => $produk,
            'perusahaan' => $perusahaan,
        ]);
    }
    // ini dipake di master, jadi ga boleh dihapus
    public static function setNumber($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $kode . $has . $n;
    }
}
