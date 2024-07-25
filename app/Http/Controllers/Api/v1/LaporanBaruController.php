<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaction;
use App\Models\DistribusiAntarToko;
use App\Models\Product;
use App\Models\Setting\Info;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanBaruController extends Controller
{
    //
    public function singleProduct()
    {
        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => request('product_id'),
            'kode_produk' => request('kode_produk')
        );
        $produk = Product::where('id', $header->product_id)->first();
        $stokMasuk = $this->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $this->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $this->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $this->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $this->getSingleDetails($header, 'FORM PENYESUAIAN');
        $distribusi = $this->getSumSingleProduct($header);


        $data = Transaction::select('id', 'nama')->where('status', 1)->where('reff', request('reff'))->first();
        $qty = 0; // ini draft
        if ($data) {
            $apem = DetailTransaction::select('id', 'qty', 'product_id')->where('transaction_id', $data->id)
                ->where('product_id', $header->product_id)->first();
            $qty = $data->nama === 'PENJUALAN' ? $apem->qty : -$apem->qty;
        }
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

        $distMB = collect($distribusi->masukbefore)->sum('qty');
        $distKB = collect($distribusi->keluarbefore)->sum('qty');
        $distMP = collect($distribusi->masukperiod)->sum('qty');
        $distKP = collect($distribusi->keluarperiod)->sum('qty');

        $sebelum = $masukBefore - $keluarBefore + $retJualBefore - $retBeliBefore + $penyeBefore + $distMB - $distKB;
        $berjalan = $masukPeriod - $keluarPeriod + $retJualPeriod - $retBeliPeriod + $penyePeriod - $qty + $distMP - $distKP;
        // $awal = $produk->stok_awal + $sebelum;
        $awal = $produk['stok_awal'] + $sebelum;
        $sekarang = $awal + $berjalan;

        $produk->stok_awal = $awal;
        $produk->stokSekarang = $sekarang;
        $produk->stokBerjalan = $berjalan;
        $produk->stok = $sekarang;
        return new JsonResponse($produk);
        // return new JsonResponse([
        //     'stok masuk' => $stokMasuk,
        //     'returPembelian' => $returPembelian,
        //     'stokKeluar' => $stokKeluar,
        //     'returPenjualan' => $returPenjualan,
        //     'penyesuaian' => $penyesuaian,
        // ]);
    }
    public function getSingleDetails($header, $nama)
    {
        $before = Transaction::select(
            'detail_transactions.qty'
        )->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $header->from)->get();
        $period = Transaction::select(
            'detail_transactions.qty'
        )->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('detail_transactions.product_id', $header->product_id)
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '=', $header->from)->get(); // period is today
        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );
        return $data;
    }
    public function getSumSingleProduct($header)
    {
        $me = Info::first();
        $masukbefore = DistribusiAntarToko::select(
            'distribusi_antar_tokos.qty'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.tujuan', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_terima', '<', $header->from)
            ->get();
        $masukperiod = DistribusiAntarToko::select(
            'distribusi_antar_tokos.qty'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.tujuan', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_terima', '=', $header->from)
            ->get();
        $keluarbefore = DistribusiAntarToko::select(
            'distribusi_antar_tokos.qty'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.dari', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_distribusi', '<', $header->from)
            ->get();
        $keluarperiod = DistribusiAntarToko::select(
            'distribusi_antar_tokos.qty'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.dari', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_distribusi', '=', $header->from) // period is today
            ->get();
        $data = (object) array(
            // 'me' => $me->kodecabang,
            'masukbefore' => $masukbefore,
            'masukperiod' => $masukperiod,
            'keluarbefore' => $keluarbefore,
            'keluarperiod' => $keluarperiod,
        );
        return $data;
    }
    public function laporanKeuangan()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        $kode = Product::orderBy(request('order_by'), request('sort'))
            ->pluck('kode_produk');
        $ongkir = $this->getDiscOngkirPeriode($header, 'PEMBELIAN');
        $pembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
        $returPembelian = $this->getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
        $penjualan = $this->getDetailsPeriodUang($header, 'PENJUALAN');
        $returPenjualan = $this->getDetailsPeriodUang($header, 'RETUR PENJUALAN');
        $beban = $this->getBebansPeriod($header, 'PENGELUARAN');
        $penerimaan = $this->getPenerimaansPeriod($header, 'PENDAPATAN');
        $distribusi = $this->getDistPeriod($header, $kode);

        // hpp = pemelian bersih + persediaan awal - persediaan akhir
        // pembelian bersih = pembelian tunai dan kredit + biaya (mis: ongkir) - potongan pembelian - retur pembelian
        // persediaan awal = nilai barang tersedia di periode awal neraca akuntansi
        // persediaan akhir = nilai barang tersedia di akhir periode transaksi


        $totalOngkir = $this->total($header, 'PEMBELIAN');
        $pembelianDgKredit = $this->getDetailsWithCredit($header, 'PEMBELIAN');
        $stok = $this->ambilAllStok();

        return new JsonResponse([
            // 'product' => $product,
            'pembelian' => $pembelian,
            'penjualan' => $penjualan,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'beban' => $beban,
            'penerimaan' => $penerimaan,
            // 'hitungPembelian' => $hitungPembelian,
            'ongkir' => $ongkir,
            'totalOngkir' => $totalOngkir,
            'pembelianDgKredit' => $pembelianDgKredit,
            'stok' => $stok,
            'distribusi' => $distribusi,

        ], 200);
    }
    public function newLaporanKeuangan()
    {
        $periode = request('selection') === 'tillToday' ? 'bulan' : request('selection');
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'periode' => $periode,
        );
        $data = CloudReportController::report($header);
        return new JsonResponse([
            'data' => $data,
            'header' => $header
        ]);
        // $kode = Product::orderBy(request('order_by'), request('sort'))
        //     ->pluck('kode_produk');
        // $ongkir = $this->getDiscOngkirPeriode($header, 'PEMBELIAN');
        // $pembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
        // $returPembelian = $this->getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
        // $penjualan = $this->getDetailsPeriodUang($header, 'PENJUALAN');
        // $returPenjualan = $this->getDetailsPeriodUang($header, 'RETUR PENJUALAN');
        // $beban = $this->getBebansPeriod($header, 'PENGELUARAN');
        // $penerimaan = $this->getPenerimaansPeriod($header, 'PENDAPATAN');
        // $distribusi = $this->getDistPeriod($header, $kode);

        // // hpp = pemelian bersih + persediaan awal - persediaan akhir
        // // pembelian bersih = pembelian tunai dan kredit + biaya (mis: ongkir) - potongan pembelian - retur pembelian
        // // persediaan awal = nilai barang tersedia di periode awal neraca akuntansi
        // // persediaan akhir = nilai barang tersedia di akhir periode transaksi


        // $totalOngkir = $this->total($header, 'PEMBELIAN');
        // $pembelianDgKredit = $this->getDetailsWithCredit($header, 'PEMBELIAN');
        // $stok = $this->ambilAllStok();

        // return new JsonResponse([
        //     // 'product' => $product,
        //     'pembelian' => $pembelian,
        //     'penjualan' => $penjualan,
        //     'returPembelian' => $returPembelian,
        //     'returPenjualan' => $returPenjualan,
        //     'beban' => $beban,
        //     'penerimaan' => $penerimaan,
        //     // 'hitungPembelian' => $hitungPembelian,
        //     'ongkir' => $ongkir,
        //     'totalOngkir' => $totalOngkir,
        //     'pembelianDgKredit' => $pembelianDgKredit,
        //     'stok' => $stok,
        //     'distribusi' => $distribusi,

        // ], 200);
    }
    //ambil diskon, ongkir, dan total pada periode dan sebelum periode tertentu
    public function getDiscOngkirPeriode($header, $nama)
    {
        $before = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
            ->where('nama', '=', $nama)
            ->where('status', '>=', 2)
            ->whereDate('tanggal', '<', $header->from)
            ->get();
        if ($header->selection === 'range') {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                ->whereDate('tanggal', '>=', $header->from)
                ->whereDate('tanggal', '<=', $header->to)
                ->get();
        } else if ($header->selection === 'tillToday') {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])
                ->get();
        } else {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
                ->where('nama', '=', $nama)
                ->where('status', '>=', 2)
                ->whereDate('tanggal', '=', $header->from)
                ->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period
        );
        return $data;
    }

    //ambil pembelian TUNAI dan NON TUNAI  pada periode  tertentu
    public function getDetailsWithCredit($header, $nama)
    {
        $trx = Transaction::select(
            'detail_transactions.product_id'
        )
            ->selectRaw('sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2);
        $this->newUntil($trx, $header);
        $jadi = $trx->groupBy('detail_transactions.product_id')->get();
        return $jadi;
    }

    public function newUntil($query, $header)
    {
        if ($header->selection === 'tillToday') {
            $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']); // bedanya disini
        } else if ($header->selection === 'spesifik') {
            $query->whereDate('tanggal', '=', $header->from);
        } else if ($header->selection === 'range') {
            $query->whereBetween('tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
        } else {
            $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']);
        }
    }
    public function ambilAllStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        $prodId = Product::orderBy(request('order_by'), request('sort'))
            ->pluck('id');
        $kode = Product::orderBy(request('order_by'), request('sort'))
            ->pluck('kode_produk');
        $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN', $prodId);
        $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN', $prodId);
        $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN', $prodId);
        $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN', $prodId);
        $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN', $prodId);
        $distribusi = $this->getDistPeriod($header, $kode);


        $product = Product::orderBy(request('order_by'), request('sort'))
            ->get();
        $data = (object) array(
            'product' => $product,
            'masuk' => $stokMasuk,
            'keluar' => $stokKeluar,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'penyesuaian' => $penyesuaian,
            'distribusi' => $distribusi,
        );
        return $data;
    }
    public function getDistPeriod($header, $kode)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';
        $me = Info::first();
        $masukbefore = DistribusiAntarToko::selectRaw(
            'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.tujuan', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_terima', '<', $sebelumBulanIni)
            ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
            ->groupBy('distribusi_antar_tokos.kode_produk')
            ->get();

        $keluarbefore = DistribusiAntarToko::selectRaw(
            'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
            ->where('header_distribusis.dari', $me->kodecabang)
            ->whereDate('header_distribusis.tgl_distribusi', '<', $sebelumBulanIni)
            ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
            ->groupBy('distribusi_antar_tokos.kode_produk')
            ->get();
        if ($header->selection === 'range') {
            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereBetween('header_distribusis.tgl_terima',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereBetween('header_distribusis.tgl_distribusi',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59']) // period is today
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
        } else if ($header->selection === 'tillToday') {

            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('header_distribusis.tgl_terima',  [date('Y-m-01') . '00:00:00', date('Y-m-t') . ' 23:59:59'])
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereBetween('header_distribusis.tgl_distribusi', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']) // period is today
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
        } else {

            $masukperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.tujuan', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereDate('header_distribusis.tgl_terima', '=', $header->from)
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
            $keluarperiod = DistribusiAntarToko::selectRaw(
                'sum(distribusi_antar_tokos.qty) as jml, distribusi_antar_tokos.product_id, distribusi_antar_tokos.harga'
            )
                ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
                // ->where('distribusi_antar_tokos.kode_produk', $header->kode_produk)
                ->where('header_distribusis.dari', $me->kodecabang)
                ->whereIn('distribusi_antar_tokos.kode_produk', $kode)
                ->whereDate('header_distribusis.tgl_distribusi', '=', $header->from) // period is today
                ->groupBy('distribusi_antar_tokos.kode_produk')
                ->get();
        }
        $data = (object) array(
            'masukbefore' => $masukbefore,
            'keluarbefore' => $keluarbefore,
            'masukperiod' => $masukperiod,
            'keluarperiod' => $keluarperiod,
        );
        return $data;
    }
    // jumlah produk sebelum periode pilihan dan pada periode pilihan
    public function getDetailsPeriod($header, $nama, $id)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';

        $before = Transaction::select(
            'transactions.id',
            'detail_transactions.product_id',
            DB::raw('sum(detail_transactions.qty) as jml')
        )
            // ->selectRaw(' sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $sebelumBulanIni)
            ->whereIn('detail_transactions.product_id', $id)
            ->groupBy('detail_transactions.product_id')
            ->get();

        if ($header->selection === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.qty) as jml')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->whereIn('detail_transactions.product_id', $id)
                ->groupBy('detail_transactions.product_id')->get();
        } else if ($header->selection === 'tillToday') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.qty) as jml'),
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                // ->whereMonth('transactions.tanggal', '=', date('m'))
                ->whereBetween('transactions.tanggal',  [date('Y-m-01') . ' 00:00:00', date('Y-m-31') . ' 23:59:59'])
                ->groupBy('detail_transactions.product_id')
                ->get();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                DB::raw('sum(detail_transactions.qty) as jml'),
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereIn('detail_transactions.product_id', $id)
                ->whereDate('transactions.tanggal', '=', $header->from)
                ->groupBy('detail_transactions.product_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }
    //ambil detail transaksi pada periode dan sebelum periode tertentu
    public function getDetailsPeriodUang($header, $nama)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';
        $before = Transaction::select(
            'transactions.id',
            'detail_transactions.product_id',
            'detail_transactions.harga',
            DB::raw('sum(detail_transactions.qty) as jml')
        )
            // ->selectRaw('sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $header->from)
            ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();

        if ($header->selection === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
        } else if ($header->selection === 'tillToday') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59'])
                ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id',
                'detail_transactions.harga',
                DB::raw('sum(detail_transactions.qty) as jml')
            )
                // ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereDate('transactions.tanggal', '=', $header->from)
                ->groupBy('detail_transactions.product_id', 'detail_transactions.harga')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }
    //ambil beban pada periode dan sebelum periode tertentu
    public function getBebansPeriod($header, $nama)
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

        if ($header->selection === 'range') {
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
        } else if ($header->selection === 'tillToday') {
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
    public function getPenerimaansPeriod($header, $nama)
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

        if ($header->selection === 'range') {
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
        } else if ($header->selection === 'tillToday') {
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
    public function total($header, $nama)
    {
        $query = Transaction::query();
        $query->selectRaw('sum(total) as jml, sum(potongan) as diskon, sum(ongkir) as ongkos, sum(totalSemua) as totalSemua')
            ->where('nama', '=', $nama)
            ->where('status', '>=', 2);
        $this->newUntil($query, $header);
        $data = $query->get();
        return $data;
    }
    public function ambilStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );

        $product = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->with('rak')->paginate(request('per_page'));

        $prodID = collect($product->items())->pluck('id');
        // $prodID = Product::orderBy(request('order_by'), request('sort'))
        //     ->filter(request(['q']))
        //     ->offset(((int)request('page') - 1) * (int)request('per_page'))
        //     ->limit(request('per_page'))
        //     ->pluck('id');
        $kode = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->offset(((int)request('page') - 1) * (int)request('per_page'))
            ->limit(request('per_page'))
            ->pluck('kode_produk');

        $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN', $prodID);
        $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN', $prodID);
        $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN', $prodID);
        $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN', $prodID);
        $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN', $prodID);
        $distribusi = $this->getDistPeriod($header, $kode);



        return new JsonResponse([
            'kode' => $kode,
            'prodID' => $prodID,
            'product' => $product,
            'masuk' => $stokMasuk,
            'keluar' => $stokKeluar,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'penyesuaian' => $penyesuaian,
            'distribusi' => $distribusi,
        ], 200);
    }
    // Transaksi pada periode terkait hanya untuk produk terpilih
    public function stokTransaction()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        // if ($header->selection === 'tillToday') {
        //     $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']); // bedanya disini
        // } else if ($header->selection === 'spesifik') {
        //     $query->whereDate('tanggal', '=', $header->from);
        // } else if ($header->selection === 'range') {
        //     $query->whereBetween('tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
        // } else {
        //     $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']);
        // }
        // $q = Transaction::select('id')->where('status', '>=', 2);
        // $this->newUntil($q, $header);
        // $anu = $q->get();
        $data = DetailTransaction::select(
            'detail_transactions.*',
            'transactions.nama',
            'transactions.tanggal',
        )
            ->leftJoin('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('detail_transactions.product_id', '=', request('id'))
            // ->with('transaction:id,nama,tanggal')
            // ->whereIn('transaction_id', $anu)
            ->when($header->selection === 'tillToday', function ($query) {
                $query->whereBetween('tanggal', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']);
            })
            ->when($header->selection === 'range', function ($query) use ($header) {
                $query->whereBetween('tanggal', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
            })
            ->when($header->selection === 'spesifik', function ($query) use ($header) {
                $query->whereDate('tanggal', '=', $header->from);
            })
            ->get();
        $me = Info::first();
        $distM = DistribusiAntarToko::select(
            'distribusi_antar_tokos.*',
            'header_distribusis.tgl_terima as tanggal'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('header_distribusis.tujuan', $me->kodecabang)
            ->where('distribusi_antar_tokos.product_id', '=', request('id'))
            ->when($header->selection === 'tillToday', function ($query) {
                $query->whereBetween('header_distribusis.tgl_terima', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']);
            })
            ->when($header->selection === 'range', function ($query) use ($header) {
                $query->whereBetween('header_distribusis.tgl_terima', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
            })
            ->when($header->selection === 'spesifik', function ($query) use ($header) {
                $query->whereDate('header_distribusis.tgl_terima', '=', $header->from);
            })
            ->get();
        $distK = DistribusiAntarToko::select(
            'distribusi_antar_tokos.*',
            'header_distribusis.tgl_distribusi as tanggal'
        )
            ->leftJoin('header_distribusis', 'header_distribusis.nodistribusi', '=', 'distribusi_antar_tokos.nodistribusi')
            ->where('header_distribusis.dari', $me->kodecabang)
            ->where('distribusi_antar_tokos.product_id', '=', request('id'))
            ->when($header->selection === 'tillToday', function ($query) {
                $query->whereBetween('header_distribusis.tgl_distribusi', [date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59']);
            })
            ->when($header->selection === 'range', function ($query) use ($header) {
                $query->whereBetween('header_distribusis.tgl_distribusi', [$header->from . ' 00:00:00', $header->to . ' 23:59:59']);
            })
            ->when($header->selection === 'spesifik', function ($query) use ($header) {
                $query->whereDate('header_distribusis.tgl_distribusi', '=', $header->from);
            })
            ->get();
        return new JsonResponse([
            'trans' => $data,
            'distm' => $distM,
            'distk' => $distK,
        ]);
    }
}
