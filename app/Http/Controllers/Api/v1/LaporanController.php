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

    public function getStok()
    {

        $masuk = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $masuk->whereHas('transaction', function ($f) {
            $f->where('nama', '=', 'PEMBELIAN')
                ->where('status', '=', 1);
            $this->until($f, request('selection'), request('from'), request('to'));
        });
        $stokMasuk = $masuk->groupBy('product_id')->get();

        $returMa = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $returMa->whereHas('transaction', function ($f) {
            $f->where('nama', '=', 'RETUR PEMBELIAN')
                ->where('status', '=', 1);
            $this->until($f, request('selection'), request('from'), request('to'));
        });
        $returPembelian = $returMa->groupBy('product_id')->get();

        $keluar = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $keluar->whereHas('transaction', function ($f) {
            $f->where('nama', '=', 'PENJUALAN')
                ->where('status', '=', 1);
            $this->until($f, request('selection'), request('from'), request('to'));
        });
        $stokKeluar = $keluar->groupBy('product_id')->get();

        $returKe = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $returKe->whereHas('transaction', function ($f) {
            $f->where('nama', '=', 'RETUR PENJUALAN')
                ->where('status', '=', 1);
            $this->until($f, request('selection'), request('from'), request('to'));
        });
        $returPenjualan = $returKe->groupBy('product_id')->get();

        $penyes = DetailTransaction::query()->selectRaw('product_id, sum(qty) as jml');
        $penyes->whereHas('transaction', function ($f) {
            $f->where('nama', '=', 'FORM PENYESUAIAN')
                ->where('status', '=', 1);
            $this->until($f, request('selection'), request('from'), request('to'));
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
        $q = DetailTransaction::query()->where('product_id', '=', request('id'))
            ->whereHas('transaction', function ($n) {
                $n->where('status', '=', 1);
                $this->until($n, request('selection'), request('from'), request('to'));
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
        $q = Transaction::query()->where('status', '=', 1);
        $this->until($q, request('selection'), request('from'), request('to'));
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
}
