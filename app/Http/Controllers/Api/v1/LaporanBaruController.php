<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanBaruController extends Controller
{
    //
    public function singleProduct()
    {
        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => request('product_id')
        );
        $stokMasuk = $this->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $this->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $this->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $this->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $this->getSingleDetails($header, 'FORM PENYESUAIAN');

        $produk = Product::where('id', $header->product_id)->first();

        $data = Transaction::select('id', 'nama')->where('status', 1)->where('reff', request('reff'))->first();
        $qty = 0;
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

        $sebelum = $masukBefore - $keluarBefore + $retJualBefore - $retBeliBefore + $penyeBefore;
        $berjalan = $masukPeriod - $keluarPeriod + $retJualPeriod - $retBeliPeriod + $penyePeriod - $qty;
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
            ->whereDate('transactions.tanggal', '=', $header->from)->get();


        $data = (object) array(
            'before' => $before,
            'period' => $period,
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
        $ongkir = $this->getDiscOngkirPeriode($header, 'PEMBELIAN');
        $pembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
        $returPembelian = $this->getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
        $penjualan = $this->getDetailsPeriodUang($header, 'PENJUALAN');
        $returPenjualan = $this->getDetailsPeriodUang($header, 'RETUR PENJUALAN');
        $beban = $this->getBebansPeriod($header, 'PENGELUARAN');
        $penerimaan = $this->getPenerimaansPeriod($header, 'PENDAPATAN');


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

        ], 200);
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
                ->whereMonth('tanggal', '=', date('m'))
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
        $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN');
        $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN');
        $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN');
        $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN');
        $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN');


        $product = Product::orderBy(request('order_by'), request('sort'))
            ->get();
        $data = (object) array(
            'product' => $product,
            'masuk' => $stokMasuk,
            'keluar' => $stokKeluar,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'penyesuaian' => $penyesuaian,
        );
        return $data;
    }
    // jumlah produk sebelum periode pilihan dan pada periode pilihan
    public function getDetailsPeriod($header, $nama)
    {
        $sebelumBulanIni = date('Y-', strtotime($header->from)) . date('m-', strtotime($header->from)) . '01 00:00:00';
        $before = Transaction::select(
            'transactions.id',
            'detail_transactions.product_id'
        )
            ->selectRaw(' sum(detail_transactions.qty) as jml')
            ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.nama', '=', $nama)
            ->where('transactions.status', '>=', 2)
            ->whereDate('transactions.tanggal', '<', $sebelumBulanIni)
            ->groupBy('detail_transactions.product_id')->get();

        if ($header->selection === 'range') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id'
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereBetween('transactions.tanggal',  [$header->from . ' 00:00:00', $header->to . ' 23:59:59'])
                ->groupBy('detail_transactions.product_id')->get();
        } else if ($header->selection === 'tillToday') {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id'
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
                ->whereMonth('transactions.tanggal', '=', date('m'))
                ->groupBy('detail_transactions.product_id')->get();
        } else {
            $period = Transaction::select(
                'transactions.id',
                'detail_transactions.product_id'
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
                ->leftJoin('detail_transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                ->where('transactions.nama', '=', $nama)
                ->where('transactions.status', '>=', 2)
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
        )
            ->selectRaw(' sum(detail_transactions.qty) as jml')
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
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
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
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
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
            )
                ->selectRaw(' sum(detail_transactions.qty) as jml')
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
                ->whereMonth('transactions.tanggal', '=', date('m'))

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
}
