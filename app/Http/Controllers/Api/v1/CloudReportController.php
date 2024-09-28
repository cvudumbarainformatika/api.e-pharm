<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Beban;
use App\Models\DistribusiAntarToko;
use App\Models\Product;
use App\Models\Setting\Info;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CloudReportController extends Controller
{
    //
    public static function report($header)
    {
        // return new JsonResponse($header);
        // $header = (object) array(
        //     'from' => request('from'),
        //     'to' => request('to'),
        //     'selection' => request('selection'),
        // );
        // $kode = Product::pluck('kode_produk');
        $masterBeban = Beban::select('id', 'nama')->get();
        // $masterBebanArr = Beban::select('id', 'nama')->get();
        // $prod = Product::selectRaw('sum(stok_awal * harga_beli) as awal')->first();
        // $ongkir = self::getDiscOngkirPeriode($header, 'PEMBELIAN');
        // $pembelian = self::getDetailsPeriodUang($header, 'PEMBELIAN');
        // $returPembelian = self::getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
        $penjualan = self::getDetailsPeriodUang($header, 'PENJUALAN');
        $returPenjualan = self::getDetailsPeriodUang($header, 'RETUR PENJUALAN');
        $beban = self::getBebansPeriod($header, 'PENGELUARAN');
        // $penerimaan = self::getPenerimaansPeriod($header, 'PENDAPATAN');
        // $distribusi = self::getDistPeriod($header, $kode);

        // hpp = pemelian bersih + persediaan awal - persediaan akhir
        // pembelian bersih = pembelian tunai dan kredit + biaya (mis: ongkir) - potongan pembelian - retur pembelian
        // persediaan awal = nilai barang tersedia di periode awal neraca akuntansi
        // persediaan akhir = nilai barang tersedia di akhir periode transaksi


        // $totalOngkir = self::total($header, 'PEMBELIAN');
        // $pembelianDgKredit = self::getDetailsWithCredit($header, 'PEMBELIAN');
        $stok = self::ambilAllStok($header);

        // metani beban
        $arr = $beban->toArray();
        foreach ($masterBeban as $key) {
            $str = $key['id'];
            $clm = array_column($arr, 'beban_id');
            $ind = array_search($str, $clm);
            $key['total'] = $ind === false ? 0 : $arr[$ind]['total'];
            // $key['arr'] = $arr;
            // $key['ind'] = $ind;
            // $key['ind s'] = $ind === false;
        }
        // hitung
        // $totalSmw = $ongkir->period->totalSemua ?? 0;
        // $total = $ongkir->period->jumlah ?? 0;;
        // $diskon = $totalSmw - $total;
        // $persediaanAwal = $prod->awal ?? 0; // jumla stok awal * harga beli
        //stok
        // $masukBefore = $stok->masuk->before->sub ?? 0;
        // $masukPeriod = $stok->masuk->period->sub ?? 0;
        // $keluarBefore = $stok->keluar->before->sub ?? 0;
        // $keluarPeriod = $stok->keluar->period->sub ?? 0;
        // $returPembelianBefore = $stok->returPembelian->before->sub ?? 0;
        // $returPembelianPeriod = $stok->returPembelian->period->sub ?? 0;
        // $returPenjualanBefore = $stok->returPenjualan->before->sub ?? 0;
        // $returPenjualanPeriod = $stok->returPenjualan->period->sub ?? 0;
        // $distribusiMasukBefore = $stok->distribusi->masukbefore->sub ?? 0;
        // $distribusiMasukPeriod = $stok->distribusi->masukperiod->sub ?? 0;
        // $distribusiKeluarBefore = $stok->distribusi->keluarbefore->sub ?? 0;
        // $distribusiKeluarPeriod = $stok->distribusi->keluarperiod->sub ?? 0;

        $bhpp = $stok->hpp->sub ?? 0;
        $retur = $stok->returPenjualan->sub ?? 0;
        $hpp = $bhpp - $retur;

        // $BstokSebelum = $masukBefore - $keluarBefore + $returPenjualanBefore - $returPembelianBefore + $distribusiMasukBefore - $distribusiKeluarBefore;
        // $masukSB = $masukBefore + $returPenjualanBefore + $distribusiMasukBefore;
        // $keluarSB = $keluarBefore + $returPembelianBefore + $distribusiKeluarBefore;
        // $stokSebelum = $masukSB - $keluarSB;
        // // $stokSebelum = $BstokSebelum < 0 ? -$BstokSebelum : $BstokSebelum;
        // // $stokBerjalan = $masukPeriod - $keluarPeriod + $returPenjualanPeriod -  $returPembelianPeriod + $distribusiMasukPeriod - $distribusiKeluarPeriod;
        // $masukP = $masukPeriod + $returPenjualanPeriod + $distribusiMasukPeriod;
        // $keluarP = $keluarPeriod + $returPembelianPeriod + $distribusiKeluarPeriod;
        // $stokBerjalan = $masukP - $keluarP;

        // $stokAwal = $persediaanAwal + $stokSebelum;
        // $stokSekarang = $stokAwal + $stokBerjalan; // persediaan akhir
        // // $persediaanAkhir = $stokSekarang < 0 ? -$stokSekarang : $stokSekarang;
        // $persediaanAkhir = $stokSekarang;

        // // hitung hpp
        // $pembelianBersih = $totalSmw - $returPembelianPeriod + $distribusiMasukBefore - $distribusiKeluarPeriod;
        // $hpp = $pembelianBersih + $persediaanAwal - $persediaanAkhir;
        // $hpp = $pembelianBersih - $stokSekarang;

        // penjualan
        $penjualanP = $penjualan->period->sub ?? 0;
        $returPenjualanP = $returPenjualan->period->sub ?? 0;
        $penjualanBersih = $penjualanP - $returPenjualanP;
        $totBeban = collect($beban)->sum('total');
        $labaRugi = $penjualanBersih - $hpp - $totBeban;
        return [
            // 'totalSmw' => $totalSmw,
            // 'total' => $total,
            // 'diskon' => $diskon,
            // 'persediaanAwal' => $persediaanAwal,
            // 'masukSB' => $masukSB,
            // 'keluarSB' => $keluarSB,
            // 'masukP' => $masukP,
            // 'keluarP' => $keluarP,
            // 'masukBefore' => $masukBefore,
            // 'masukPeriod' => $masukPeriod,
            // 'keluarBefore' => $keluarBefore,
            // 'keluarPeriod' => $keluarPeriod,
            // 'returPembelianBefore' => $returPembelianBefore,
            // 'returPembelianPeriod' => $returPembelianPeriod,
            // 'returPenjualanBefore' => $returPenjualanBefore,
            // 'returPenjualanPeriod' => $returPenjualanPeriod,
            // 'distribusiMasukBefore' => $distribusiMasukBefore,
            // 'distribusiMasukPeriod' => $distribusiMasukPeriod,
            // 'distribusiKeluarBefore' => $distribusiKeluarBefore,
            // 'distribusiKeluarPeriod' => $distribusiKeluarPeriod,
            // 'stokSebelum' => $stokSebelum,
            // 'stokAwal' => $stokAwal,
            // 'stokBerjalan' => $stokBerjalan,
            // 'stokSekarang' => $stokSekarang,
            // 'pembelianBersih' => $pembelianBersih,
            'hpp' => $hpp,
            'penjualanP' => $penjualanP,
            'penjualanBersih' => $penjualanBersih,
            'returPenjualanP' => $returPenjualanP,
            'totBeban' => $totBeban,
            'labaRugi' => $labaRugi,
            'masterBeban' => $masterBeban,
            // 'persediaanAkhir' => $persediaanAkhir,
            // 'pembelian' => $pembelian,
            // 'penjualan' => $penjualan,
            // 'returPembelian' => $returPembelian,
            // 'returPenjualan' => $returPenjualan,
            'beban' => $beban,
            // 'penerimaan' => $penerimaan,
            // 'ongkir' => $ongkir,
            // 'totalOngkir' => $totalOngkir,
            // 'pembelianDgKredit' => $pembelianDgKredit,
            'stok' => $stok,
            // 'distribusi' => $distribusi,
            // 'prod' => $prod,

        ];
    }

    public static function newHpp($header, $nama, $id)
    {
        if ($header->periode === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.qty) as jml'),
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->whereIn('detail_transactions.product_id', $id)
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.sub_total) as sub'),
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-31') . ' 23:59:59'])
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                ->whereDate('transactions.tanggal', '=', $header->from)
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        }
        return $period;
    }
    //ambil detail transaksi pada periode dan sebelum periode tertentu
    public static function getDetailsPeriodUang($header, $nama)
    {
        // $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';
        $before = Transaction::select(
            'transactions.id',
            'detail_transactions.product_id',
            'detail_transactions.harga',
            DB::raw('sum(detail_transactions.qty) as jml'),
            DB::raw('sum(detail_transactions.sub_total) as sub'),
            DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr')
        )
            // ->selectRaw('sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $header->from)
            // ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
            ->first();

        if ($header->periode === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml'),
                DB::raw('sum(detail_transactions.sub_total) as sub'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                // ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
                ->first();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml'),
                DB::raw('sum(detail_transactions.sub_total) as sub'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])
                // ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
                ->first();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml'),
                DB::raw('sum(detail_transactions.sub_total) as sub'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereDate('transactions.tanggal', '=', $header->from)
                // ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
                ->first();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }
    //ambil beban pada periode dan sebelum periode tertentu
    public static function getBebansPeriod($header, $nama)
    {
        $before = Transaction::select(
            'beban_transactions.beban_id',
        )
            ->selectRaw(' sum(beban_transactions.sub_total) as total')
            ->leftJoin('beban_transactions', 'beban_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->where('transactions.jenis', '=', 'tunai')
            ->where('transactions.supplier_id', null)
            ->whereDate('transactions.tanggal', '<', $header->from)
            ->groupBy('beban_transactions.beban_id')->get();

        if ($header->periode === 'range') {
            $period = Transaction::select(
                'beban_transactions.beban_id',
            )
                ->selectRaw(' sum(beban_transactions.sub_total) as total')
                ->leftJoin('beban_transactions', 'beban_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                ->where('transactions.supplier_id', null)
                ->whereBetween('transactions.tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->groupBy('beban_transactions.beban_id')->get();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::select(
                'beban_transactions.beban_id',
            )
                ->selectRaw(' sum(beban_transactions.sub_total) as total')
                ->leftJoin('beban_transactions', 'beban_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                ->where('transactions.supplier_id', null)
                ->whereBetween('transactions.tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])
                ->groupBy('beban_transactions.beban_id')->get();
        } else {
            $period = Transaction::select(
                'beban_transactions.beban_id',
            )
                ->selectRaw(' sum(beban_transactions.sub_total) as total')
                ->leftJoin('beban_transactions', 'beban_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                ->where('transactions.supplier_id', null)
                ->whereDate('transactions.tanggal', '=', $header->from)
                ->groupBy('beban_transactions.beban_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }

    //ambil penerimaan pada periode dan sebelum periode tertentu
    public static function getPenerimaansPeriod($header, $nama)
    {

        $before = Transaction::select(
            'detail_penerimaans.penerimaan_id',
        )
            ->selectRaw(' sum(detail_penerimaans.sub_total) as total')
            ->leftJoin('detail_penerimaans', 'detail_penerimaans.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->where('transactions.jenis', '=', 'tunai')
            ->whereDate('transactions.tanggal', '<', $header->from)
            ->groupBy('detail_penerimaans.penerimaan_id')->get();

        if ($header->periode === 'range') {
            $period = Transaction::select(
                'detail_penerimaans.penerimaan_id',
            )
                ->selectRaw(' sum(detail_penerimaans.sub_total) as total')
                ->leftJoin('detail_penerimaans', 'detail_penerimaans.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                ->whereDate('transactions.tanggal', '>=', $header->from)
                ->whereDate('transactions.tanggal', '<=', $header->to)
                ->groupBy('detail_penerimaans.penerimaan_id')->get();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::select(
                'detail_penerimaans.penerimaan_id',
            )
                ->selectRaw(' sum(detail_penerimaans.sub_total) as total')
                ->leftJoin('detail_penerimaans', 'detail_penerimaans.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])

                ->groupBy('detail_penerimaans.penerimaan_id')->get();
        } else {
            $period = Transaction::select(
                'detail_penerimaans.penerimaan_id',
            )
                ->selectRaw(' sum(detail_penerimaans.sub_total) as total')
                ->leftJoin('detail_penerimaans', 'detail_penerimaans.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->where('transactions.jenis', '=', 'tunai')
                ->whereDate('transactions.tanggal', '=', $header->from)
                ->groupBy('detail_penerimaans.penerimaan_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }
    public static function getDistPeriod($header, $kode)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';
        $me = Info::first();
        $masukbefore = DistribusiAntarToko::selectRaw(
            'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.tujuan', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_terima', '<', $sebelumBulanIni)
            ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
            // ->groupBy('distribusi_antar_tokos.kode_produk')
            ->first();
        // ->get();

        $keluarbefore = DistribusiAntarToko::selectRaw(
            'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.dari', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_distribusi', '<', $sebelumBulanIni)
            ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
            // ->groupBy('distribusi_antar_tokos.kode_produk')
            ->first();
        // ->get();
        if ($header->periode === 'range') {
            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereBetween('header_distribusis.tgl_terima',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereBetween('header_distribusis.tgl_distribusi',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59']) // period is today
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
        } else if ($header->periode === 'bulan') {

            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('header_distribusis.tgl_terima',  [date('Y-m-01') . '00:00:00', date('Y-m-t') . ' 23:59:59'])
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereBetween('header_distribusis.tgl_distribusi', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']) // period is today
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
        } else {

            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereDate('header_distribusis.tgl_terima', '=', $header->from)
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, sum(distribusi_antar_tokos.qty * distribusi_antar_tokos.harga) as sub, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereDate('header_distribusis.tgl_distribusi', '=', $header->from) // period is today
                // ->groupBy('distribusi_antar_tokos.kode_produk')
                ->first();
            // ->get();
        }
        $data = (object) array(
            'masukbefore' => $masukbefore,
            'keluarbefore' => $keluarbefore,
            'masukperiod' => $masukperiod,
            'keluarperiod' => $keluarperiod,
        );
        return $data;
    }
    //ambil diskon, ongkir, dan total pada periode dan sebelum periode tertentu
    public static function getDiscOngkirPeriode($header, $nama)
    {
        $before = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
            ->where('nama', '=', $nama)
            ->where('status', '>=', 2)
            ->whereDate('tanggal', '<', $header->from)
            ->first();
        if ($header->periode === 'range') {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                ->whereDate('tanggal', '>=', $header->from)
                ->whereDate('tanggal', '<=', $header->to)
                ->first();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])
                ->first();
        } else {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                ->whereDate('tanggal', '=', $header->from)
                ->first();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period
        );
        return $data;
    }
    public static function total($header, $nama)
    {
        $query = Transaction::query();
        $query->selectRaw('sum(total) as jml, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
            ->where('nama', '=', $nama)
            ->where('status', '>=', 2);
        self::newUntil($query, $header);
        $data = $query->get();
        return $data;
    }
    public static function ambilAllStok($header)
    {
        // $header = (object) array(
        //     'from' => request('from'),
        //     'to' => request('to'),
        //     'selection' => request('selection'),
        // );
        $prodId = Product::pluck('id');
        $kode = Product::pluck('kode_produk');
        // $stokMasuk = self::getDetailsPeriod($header, 'PEMBELIAN', $prodId);
        // $returPembelian = self::getDetailsPeriod($header, 'RETUR PEMBELIAN', $prodId);
        // $stokKeluar = self::getDetailsPeriod($header, 'PENJUALAN', $prodId);
        // $returPenjualan = self::getDetailsPeriod($header, 'RETUR PENJUALAN', $prodId);
        // $penyesuaian = self::getDetailsPeriod($header, 'FORM PENYESUAIAN', $prodId);
        // $distribusi = self::getDistPeriod($header, $kode);
        $hpp = self::newHpp($header, 'PENJUALAN', $prodId);
        $returPenjualan = self::newHpp($header, 'RETUR PENJUALAN', $prodId);


        // $product = Product::get();
        $data = (object) array(
            // 'product' => $product,
            // 'masuk' => $stokMasuk,
            // 'keluar' => $stokKeluar,
            // 'returPembelian' => $returPembelian,
            // 'returPenjualan' => $returPenjualan,
            // 'penyesuaian' => $penyesuaian,
            // 'distribusi' => $distribusi,
            'hpp' => $hpp,
            'returPenjualan' => $returPenjualan,
        );
        return $data;
    }
    //ambil pembelian TUNAI dan NON TUNAI  pada periode  tertentu
    public static function getDetailsWithCredit($header, $nama)
    {
        $trx = Transaction::select(
            'detail_transactions.product_id'
        )
            ->selectRaw('sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2);
        self::newUntil($trx, $header);
        $jadi = $trx->groupBy('detail_transactions.product_id')->get();
        return $jadi;
    }
    public static function newUntil($query, $header)
    {
        if ($header->periode === 'bulan') {
            $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']); // bedanya disini
        }
        // else if ($header->periode === 'spesifik') {
        //     $query->whereDate('tanggal', '=', $header->from);
        // }
        else if ($header->periode === 'range') {
            $query->whereBetween('tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
        } else {
            $query->whereDate('tanggal', '=', $header->from);
        }
    }

    // jumlah produk sebelum periode pilihan dan pada periode pilihan
    public static function  getDetailsPeriod($header, $nama, $id)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';

        $before = Transaction::select(
            'transactions.id',
            'detail_transactions.product_id',
            DB::raw('sum(detail_transactions.qty) as jml'),
            DB::raw('sum(detail_transactions.sub_total) as subt'),
            DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
            DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
        )
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $sebelumBulanIni)
            ->whereIn('detail_transactions.product_id', $id)
            // ->groupBy('detail_transactions.product_id')
            ->first();
        // ->get();

        if ($header->periode === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.qty) as jml'),
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->whereIn('detail_transactions.product_id', $id)
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        } else if ($header->periode === 'bulan') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.sub_total) as sub'),
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-31') . ' 23:59:59'])
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.sub_total) as subt'),
                DB::raw('sum(detail_transactions.qty*detail_transactions.harga) as subfr'),
                DB::raw('sum(detail_transactions.qty*products.harga_beli) as sub')
            )
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'products.id', '=', 'detail_transactions.product_id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                ->whereDate('transactions.tanggal', '=', $header->from)
                // ->groupBy('detail_transactions.product_id')
                ->first();
            // ->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }
}
