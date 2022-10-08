<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\BebanTransaction;
use App\Models\Customer;
use App\Models\DetailPenerimaan;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // fungsi periode
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

    // fungsi periode
    public function until($query, $header)
    {
        if ($header->selection === 'tillToday') {
            $query->whereDate('tanggal', '<=', date('Y-m-d'));
        } else if ($header->selection === 'spesifik') {
            $query->whereDate('tanggal', '=', $header->from);
        } else if ($header->selection === 'range') {
            $query->whereDate('tanggal', '>=', $header->from)->whereDate('tanggal', '<=', $header->to);
        }
    }
    public function newUntil($query, $header)
    {
        if ($header->selection === 'tillToday') {
            $query->whereDate('tanggal', '=', date('Y-m-d')); // bedanya disini
        } else if ($header->selection === 'spesifik') {
            $query->whereDate('tanggal', '=', $header->from);
        } else if ($header->selection === 'range') {
            $query->whereDate('tanggal', '>=', $header->from)->whereDate('tanggal', '<=', $header->to);
        }
    }
    // sepertinya ga ada yang pake, ga bisa masuk query
    public function getPeriod($query, $header)
    {
        $beforePeriod = $query->whereDate('tanggal', '<', $header->from);
        $Period = $query->whereDate('tanggal', '>=', $header->from)->whereDate('tanggal', '<=', $header->to);
        $data = (object) array(
            'beforePeriod' => $beforePeriod,
            'period' => $Period
        );
        return $data;
    }

    // jumlah produk hingga periode pilihan
    public function getDetails($header, $nama)
    {
        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('status', '=', 1)
                ->whereDate('tanggal', '=', $header->from);
            // $this->until($f, $header);
        });

        $data = $masuk->groupBy('product_id')->get();
        return $data;
    }

    // jumlah produk sebelum periode pilihan dan pada periode pilihan
    public function getDetailsPeriod($header, $nama)
    {

        $before = DetailTransaction::selectRaw('product_id, sum(qty) as jml')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('product_id')->get();

        if ($header->selection === 'range') {
            $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->whereDate('tanggal', '>=', $header->from)
                        ->whereDate('tanggal', '<=', $header->to);
                })->groupBy('product_id')->get();
        } else {
            $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->whereDate('tanggal', '=', date('Y-m-d'));
                })->groupBy('product_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }

    // ambil jumlah produk berdasarkan nama transaksi terkait
    public function ambilStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        if ($header->selection === 'spesifik') {
            $stokMasuk = $this->getDetails($header, 'PEMBELIAN');
            $returPembelian = $this->getDetails($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetails($header, 'PENJUALAN');
            $returPenjualan = $this->getDetails($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetails($header, 'FORM PENYESUAIAN');
        } else {
            $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN');
            $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN');
            $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN');
        }

        $product = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->with('rak')->paginate(request('per_page'));

        return new JsonResponse([
            'product' => $product,
            'masuk' => $stokMasuk,
            'keluar' => $stokKeluar,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'penyesuaian' => $penyesuaian,
        ], 200);
    }

    // lihat detail transaksi produk bersangkutan
    public function moreStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        $q = DetailTransaction::query()->where('product_id', '=', request('id'))
            ->whereHas('transaction', function ($n) use ($header) {
                $n->where('status', '=', 1);
                $this->until($n, $header);
            });
        $details = $q->orderBy(request('order_by'), request('sort'))->paginate(request('per_page'));
        $product = Product::find(request('id'));
        $product->details = $details;

        return new JsonResponse($product);
    }
    // Transaksi pada periode terkait hanya untuk produk terpilih
    public function stokTransaction()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        $q = Transaction::query()->where('status', '=', 1);
        $this->until($q, $header);
        $q->whereHas('detail_transaction', function ($m) {
            $m->where('product_id', '=', request('id'));
        });;
        $data = $q->with('detail_transaction')->latest('tanggal')->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    // belum dipake sepertinya
    public function cari()
    {
        $q = Transaction::filter(['product'])->with('detail_transaction.product');
        $data = $q->get();
        return new JsonResponse($data);
    }

    // ambil jumlah hutang supplier
    public function getHutangSupplier()
    {
        $query = DetailTransaction::query()->selectRaw('product_id, harga, sum(qty) as jml');
        $query->whereHas('transaction', function ($gg) {
            $gg->where('nama', '=', 'PEMBELIAN')
                ->where('status', '=', 1)
                ->where('jenis', '=', 'hutang')
                ->where('supplier_id', '=', request('supplier_id'))
                ->whereDate('tanggal', '<=', date('Y-m-d'));
        });
        $hutang = $query->groupBy('product_id', 'harga')
            // ->with(['product'])
            ->get();
        $dibayar = BebanTransaction::selectRaw('beban_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($apem) {
                $apem->where('nama', '=', 'BEBAN')
                    ->where('status', '=', 1)
                    ->where('supplier_id', '=', request('supplier_id'))
                    ->whereDate('tanggal', '<=', date('Y-m-d'));
            });
        $supplier = Supplier::find(request('supplier_id'));


        return new JsonResponse(['hutang' => $hutang, 'dibayar' => $dibayar, 'awal' => $supplier->saldo_awal_hutang]);
    }

    // ambil jumlah piutng customer
    public function getPiutangCustomer()
    {
        $query = DetailTransaction::query()->selectRaw('product_id, harga, sum(qty) as jml');
        $query->whereHas('transaction', function ($gg) {
            $gg->where('nama', '=', 'PENJUALAN')
                ->where('status', '=', 1)
                ->where('jenis', '=', 'piutang')
                ->where('customer_id', '=', request('customer_id'))
                ->whereDate('tanggal', '<=', date('Y-m-d'));
        });
        $hutang = $query->groupBy('product_id', 'harga')
            // ->with(['product'])
            ->get();
        $dibayar = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')->whereHas('transaction', function ($apem) {
            $apem->where('nama', '=', 'PENERIMAAN')
                ->where('status', '=', 1)
                ->where('customer_id', '=', request('customer_id'))
                ->whereDate('tanggal', '<=', date('Y-m-d'));
        });
        $customer = Customer::find(request('customer_id'));


        return new JsonResponse(['hutang' => $hutang, 'dibayar' => $dibayar, 'awal' => $customer->saldo_awal_piutang]);
    }

    // hitung total uang yang ada di tabel transaksi
    // jika ada supplier / customer / dokter / umum
    // hitung transaksi milik _id terkait
    public function getTotalByDate()
    {
        $query = Transaction::query();
        $query->selectRaw('sum(total) as jml, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', request('nama'))
            ->where('status', '=', 1)
            ->when(request('supplier_id'), function ($sp, $q) {
                return $sp->where('supplier_id', '=', $q);
            })
            ->when(request('customer_id'), function ($sp) {
                return $sp->where('customer_id', '=', request('customer_id'));
            })
            ->when(request('dokter_id'), function ($sp) {
                return $sp->where('dokter_id', '=', request('dokter_id'));
            })
            ->when(request('umum'), function ($sp) {
                return $sp->where('dokter_id', '=', null)
                    ->where('customer_id', '=', null);
            });
        // ->with(['detail_transaction', 'penerimaan_transaction', 'beban_transaction', 'dokter', 'customer', 'supplier'])
        $this->periode($query, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);
        $data = $query->get();

        return new JsonResponse($data);
    }



    // ambil transaksi yang ada di tabel transaksi berdasarkan periode
    // jika ada supplier / customer / dokter / umum
    // hitung transaksi milik _id terkait
    public function getByDate()
    {
        $query = Transaction::query();
        // ->selectRaw('product_id, harga, sum(qty) as jml');
        // $query->whereHas('transaction', function ($gg) {
        //     $gg->where(['nama' => request('nama'), 'status' => 1]);

        // });
        $query->where('nama', '=', request('nama'))
            ->where('status', '=', 1)
            ->when(request('supplier_id'), function ($sp, $q) {
                return $sp->where('supplier_id', '=', $q);
            })
            ->when(request('customer_id'), function ($sp) {
                return $sp->where('customer_id', '=', request('customer_id'));
            })
            ->when(request('dokter_id'), function ($sp) {
                return $sp->where('dokter_id', '=', request('dokter_id'));
            })
            ->when(request('umum'), function ($sp) {
                return $sp->where('dokter_id', '=', null)
                    ->where('customer_id', '=', null);
            });
        $this->periode($query, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);



        // $data = $query->groupBy('product_id', 'harga')
        //     ->with(['product'])
        //     ->get();
        // return new JsonResponse($data);
        $data = $query->with(['detail_transaction.product', 'penerimaan_transaction.penerimaan', 'beban_transaction.beban', 'dokter', 'customer', 'supplier', 'kasir'])
            ->latest()->paginate(request('per_page'));

        return TransactionResource::collection($data);
    }

    //ambil beban pada periode tertentu
    public function getBebans($header, $nama)
    {
        $masuk = BebanTransaction::query()->selectRaw('beban_id, sum(sub_total) as total');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('jenis', '=', 'tunai')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });

        $data = $masuk->groupBy('beban_id')->get();
        return $data;
    }

    //ambil beban pada periode dan sebelum periode tertentu
    public function getBebansPeriod($header, $nama)
    {

        $before = BebanTransaction::selectRaw('beban_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('beban_id')->get();

        if ($header->selection === 'range') {
            $period = BebanTransaction::selectRaw('beban_id,  sum(sub_total) as total')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '>=', $header->from)
                        ->whereDate('tanggal', '<=', $header->to);
                })->groupBy('beban_id')->get();
        } else {
            $period = BebanTransaction::selectRaw('beban_id,  sum(sub_total) as total')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '=', date('Y-m-d'));
                })->groupBy('beban_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }

    //ambil penerimaan pada periode tertentu
    public function getPenerimaans($header, $nama)
    {
        $masuk = DetailPenerimaan::query()->selectRaw('penerimaan_id, sum(sub_total) as total');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('jenis', '=', 'tunai')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });

        $data = $masuk->groupBy('penerimaan_id')->get();
        return $data;
    }


    //ambil penerimaan pada periode dan sebelum periode tertentu
    public function getPenerimaansPeriod($header, $nama)
    {

        $before = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('penerimaan_id')->get();

        if ($header->selection === 'range') {
            $period = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '>=', $header->from)
                        ->whereDate('tanggal', '<=', $header->to);
                })->groupBy('penerimaan_id')->get();
        } else {
            $period = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '=', date('Y-m-d'));
                })->groupBy('penerimaan_id')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }


    //ambil detail transaksi pada periode  tertentu
    public function getDetailsUang($header, $nama)
    {
        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml,  harga');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('jenis', '=', 'tunai')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });

        $data = $masuk->groupBy('product_id', 'harga')->get();
        return $data;
    }
    //ambil pembelian TUNAI dan NON TUNAI  pada periode  tertentu
    public function getDetailsWithCredit($header, $nama)
    {
        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('status', '=', 1);
            $this->newUntil($f, $header);
        });

        $data = $masuk->groupBy('product_id')->get();
        return $data;
    }

    //ambil detail transaksi pada periode dan sebelum periode tertentu
    public function getDetailsPeriodUang($header, $nama)
    {

        $before = DetailTransaction::selectRaw('product_id, sum(qty) as jml, harga')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('product_id', 'harga')->get();

        if ($header->selection === 'range') {
            $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml, harga')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '>=', $header->from)
                        ->whereDate('tanggal', '<=', $header->to);
                })->groupBy('product_id', 'harga')->get();
        } else {
            $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml, harga')
                ->whereHas('transaction', function ($f) use ($header, $nama) {
                    $f->where('nama', '=', $nama)
                        ->where('status', '=', 1)
                        ->where('jenis', '=', 'tunai')
                        ->whereDate('tanggal', '=', date('Y-m-d'));
                })->groupBy('product_id', 'harga')->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }


    //ambil diskon, ongkir, dan total pada periode dan sebelum periode tertentu
    public function getDiscOngkirPeriode($header, $nama)
    {
        $before = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1)
            ->whereDate('tanggal', '<', $header->from)
            ->get();
        if ($header->selection === 'range') {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
                ->where('nama', '=', $nama)
                ->where('status', '=', 1)
                ->whereDate('tanggal', '>=', $header->from)
                ->whereDate('tanggal', '<=', $header->to)
                ->get();
        } else {
            $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
                ->where('nama', '=', $nama)
                ->where('status', '=', 1)
                ->whereDate('tanggal', '=', date('Y-m-d'))
                ->get();
        }

        $data = (object) array(
            'before' => $before,
            'period' => $period
        );
        return $data;
    }


    //ambil ongkir, diskon dan total pada periode tertentu
    public function getDiscOngkir($header, $nama)
    {
        $data = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1)
            ->whereDate('tanggal', '=', $header->from)
            ->get();
        return $data;
    }

    // ambil apa yang dutuhkan  oleh laporan keuangan
    public function laporanKeuangan()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        if ($header->selection === 'spesifik') {
            $ongkir = $this->getDiscOngkir($header, 'PEMBELIAN');
            $pembelian = $this->getDetailsUang($header, 'PEMBELIAN');
            $returPembelian = $this->getDetailsUang($header, 'RETUR PEMBELIAN');
            $penjualan = $this->getDetailsUang($header, 'PENJUALAN');
            $returPenjualan = $this->getDetailsUang($header, 'RETUR PENJUALAN');
            $beban = $this->getBebans($header, 'BEBAN');
            $penerimaan = $this->getPenerimaans($header, 'PENERIMAAN');
        } else {
            $ongkir = $this->getDiscOngkirPeriode($header, 'PEMBELIAN');
            $pembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
            $returPembelian = $this->getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
            $penjualan = $this->getDetailsPeriodUang($header, 'PENJUALAN');
            $returPenjualan = $this->getDetailsPeriodUang($header, 'RETUR PENJUALAN');
            $beban = $this->getBebansPeriod($header, 'BEBAN');
            $penerimaan = $this->getPenerimaansPeriod($header, 'PENERIMAAN');
        }
        $product = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->with('rak')->paginate(request('per_page'));

        // hpp = pemelian bersih + persediaan awal - persediaan akhir
        // pembelian bersih = pembelian tunai dan kredit + biaya (mis: ongkir) - potongan pembelian - retur pembelian
        // persediaan awal = nilai barang tersedia di periode awal neraca akuntansi
        // persediaan akhir = nilai barang tersedia di akhir periode transaksi


        $totalOngkir = $this->total($header, 'PEMBELIAN');
        $pembelianDgKredit = $this->getDetailsWithCredit($header, 'PEMBELIAN');
        $stok = $this->ambilAllStok();
        return new JsonResponse([
            'product' => $product,
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
    public function total($header, $nama)
    {
        $query = Transaction::query();
        $query->selectRaw('sum(total) as jml, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1);
        $this->until($query, $header);
        $data = $query->get();
        return $data;
    }
    public function ambilAllStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        if ($header->selection === 'spesifik') {
            $stokMasuk = $this->getDetails($header, 'PEMBELIAN');
            $returPembelian = $this->getDetails($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetails($header, 'PENJUALAN');
            $returPenjualan = $this->getDetails($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetails($header, 'FORM PENYESUAIAN');
        } else {
            $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN');
            $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN');
            $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN');
        }

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
}
