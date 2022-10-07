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

    public function until($query, $header)
    {
        if ($header->selection === 'tillToday') {
            $query->whereDate('tanggal', '<=', date('Y-m-d'));
        } else if ($header->selection === 'spesifik') {
            $query->whereDate('tanggal', '<=', $header->from);
        } else if ($header->selection === 'range') {
            $query->whereDate('tanggal', '>=', $header->from)->whereDate('tanggal', '<=', $header->to);
        }
    }

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

    public function getDetails($header, $nama)
    {
        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $masuk->whereHas('transaction', function ($f) use ($header, $nama) {
            $f->where('nama', '=', $nama)
                ->where('status', '=', 1);
            $this->until($f, $header);
        });

        $data = $masuk->groupBy('product_id')->get();
        return $data;
    }

    public function getDetailsPeriod($header, $nama)
    {

        $before = DetailTransaction::selectRaw('product_id, sum(qty) as jml')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('product_id')->get();

        $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->whereDate('tanggal', '>=', $header->from)
                    ->whereDate('tanggal', '<=', $header->to);
            })->groupBy('product_id')->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }

    public function ambilStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        if ($header->selection === 'range') {
            $stokMasuk = $this->getDetailsPeriod($header, 'PEMBELIAN');
            $returPembelian = $this->getDetailsPeriod($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetailsPeriod($header, 'PENJUALAN');
            $returPenjualan = $this->getDetailsPeriod($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetailsPeriod($header, 'FORM PENYESUAIAN');
        } else {
            $stokMasuk = $this->getDetails($header, 'PEMBELIAN');
            $returPembelian = $this->getDetails($header, 'RETUR PEMBELIAN');
            $stokKeluar = $this->getDetails($header, 'PENJUALAN');
            $returPenjualan = $this->getDetails($header, 'RETUR PENJUALAN');
            $penyesuaian = $this->getDetails($header, 'FORM PENYESUAIAN');
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

    public function getStok()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );

        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $masuk->whereHas('transaction', function ($f) use ($header) {
            $f->where('nama', '=', 'PEMBELIAN')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });
        $stokMasuk = $masuk->groupBy('product_id')->get();

        $returMa = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $returMa->whereHas('transaction', function ($f) use ($header) {
            $f->where('nama', '=', 'RETUR PEMBELIAN')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });
        $returPembelian = $returMa->groupBy('product_id')->get();

        $keluar = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $keluar->whereHas('transaction', function ($f) use ($header) {
            $f->where('nama', '=', 'PENJUALAN')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });
        $stokKeluar = $keluar->groupBy('product_id')->get();

        $returKe = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $returKe->whereHas('transaction', function ($f) use ($header) {
            $f->where('nama', '=', 'RETUR PENJUALAN')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });
        $returPenjualan = $returKe->groupBy('product_id')->get();

        $penyes = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $penyes->whereHas('transaction', function ($f) use ($header) {
            $f->where('nama', '=', 'FORM PENYESUAIAN')
                ->where('status', '=', 1);
            $this->until($f, $header);
        });
        $penyesuaian = $penyes->groupBy('product_id')->get();

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
        // return new JsonResponse([
        //     'product' => $product,
        //     'details' => $details
        // ]);
    }

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

    public function cari()
    {
        $q = Transaction::filter(['product'])->with('detail_transaction.product');
        $data = $q->get();
        return new JsonResponse($data);
    }

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

    public function getBebansPeriod($header, $nama)
    {

        $before = BebanTransaction::selectRaw('beban_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('beban_id')->get();

        $period = BebanTransaction::selectRaw('beban_id,  sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '>=', $header->from)
                    ->whereDate('tanggal', '<=', $header->to);
            })->groupBy('beban_id')->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }
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

    public function getPenerimaansPeriod($header, $nama)
    {

        $before = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('penerimaan_id')->get();

        $period = DetailPenerimaan::selectRaw('penerimaan_id, sum(sub_total) as total')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '>=', $header->from)
                    ->whereDate('tanggal', '<=', $header->to);
            })->groupBy('penerimaan_id')->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data->period;
    }
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

    public function getDetailsPeriodUang($header, $nama)
    {

        $before = DetailTransaction::selectRaw('product_id, sum(qty) as jml, harga')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '<', $header->from);
            })->groupBy('product_id', 'harga')->get();

        $period = DetailTransaction::selectRaw('product_id, sum(qty) as jml, harga')
            ->whereHas('transaction', function ($f) use ($header, $nama) {
                $f->where('nama', '=', $nama)
                    ->where('status', '=', 1)
                    ->where('jenis', '=', 'tunai')
                    ->whereDate('tanggal', '>=', $header->from)
                    ->whereDate('tanggal', '<=', $header->to);
            })->groupBy('product_id', 'harga')->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period,
        );

        return $data;
    }

    public function getDiscOngkirPeriode($header, $nama)
    {
        $before = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1)
            ->whereDate('tanggal', '<', $header->from)
            ->get();
        $period = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1)
            ->whereDate('tanggal', '>=', $header->from)
            ->whereDate('tanggal', '<=', $header->to)
            ->get();

        $data = (object) array(
            'before' => $before,
            'period' => $period
        );
        return $data;
    }

    public function getDiscOngkir($nama)
    {
        $data = Transaction::selectRaw('sum(total) as jumlah, sum(potongan) as diskon, sum(ongkir) as ongkos')
            ->where('nama', '=', $nama)
            ->where('status', '=', 1)
            ->whereDate('tanggal', '<=', date('Y-m-d'))
            ->get();
        return $data;
    }
    public function laporanKeuangan()
    {
        $header = (object) array(
            'from' => request('from'),
            'to' => request('to'),
            'selection' => request('selection'),
        );
        // if ($header->selection === 'range') {
        //     $pembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
        //     $returPembelian = $this->getDetailsPeriodUang($header, 'RETUR PEMBELIAN');
        //     $penjualan = $this->getDetailsPeriodUang($header, 'PENJUALAN');
        //     $returPenjualan = $this->getDetailsPeriodUang($header, 'RETUR PENJUALAN');
        //     $beban = $this->getBebansPeriod($header, 'BEBAN');
        //     $penerimaan = $this->getPenerimaansPeriod($header, 'PENERIMAAN');
        // } else {
        $pembelian = $this->getDetailsUang($header, 'PEMBELIAN');
        $returPembelian = $this->getDetailsUang($header, 'RETUR PEMBELIAN');
        $penjualan = $this->getDetailsUang($header, 'PENJUALAN');
        $returPenjualan = $this->getDetailsUang($header, 'RETUR PENJUALAN');
        $beban = $this->getBebans($header, 'BEBAN');
        $penerimaan = $this->getPenerimaans($header, 'PENERIMAAN');
        // }
        $product = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->with('rak')->paginate(request('per_page'));

        // hpp
        if ($header->selection === 'range') {
            $ongkir = $this->getDiscOngkirPeriode($header, 'PEMBELIAN');
            $hitungPembelian = $this->getDetailsPeriodUang($header, 'PEMBELIAN');
        } else {
            $ongkir = $this->getDiscOngkir('PEMBELIAN');
            $hitungPembelian = $this->getDetailsUang($header, 'PEMBELIAN');
        }


        return new JsonResponse([
            'product' => $product,
            'pembelian' => $pembelian,
            'penjualan' => $penjualan,
            'returPembelian' => $returPembelian,
            'returPenjualan' => $returPenjualan,
            'beban' => $beban,
            'penerimaan' => $penerimaan,
            'hitungPembelian' => $hitungPembelian,
            'ongkir' => $ongkir,

        ], 200);
    }
}
