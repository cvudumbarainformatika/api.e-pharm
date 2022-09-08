<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\DetailTransactionResource;
use App\Http\Resources\v1\ProductResource;
use App\Http\Resources\v1\TransactionResource;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DetailTransactionController extends Controller
{
    public function index()
    {
        // $data = DetailTransaction::paginate();
        $data = DetailTransaction::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        $data->load('product');
        return DetailTransactionResource::collection($data);
    }
    public function getById()
    {
        // $data = DetailTransaction::paginate();
        $data = DetailTransaction::where('transaction_id', request()->transaction_id)->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        $data->load('product');
        return DetailTransactionResource::collection($data);
    }


    public function getExpired()
    {
        $data = DetailTransaction::whereNotNull('expired')->get();
        $data2 = [];
        foreach ($data as &$value) {
            array_push($data2, $value->expired);
        }
        $filterd = array_unique($data2);
        // $filterd = isset($data['expired']);
        return response()->json([
            'data2' => $data2,
            'filtered' => $filterd,
            'data' => $data
        ]);
    }

    public function getByDate()
    {
        // between

        // $from = date('2022-08-29'); // tanggal yang menjadi parameter tidak masuk dalam data yang di caari
        // $to = date('2022-09-04'); // jadi rumus nya (from < x < to) ga ada '=' nya..

        // $data = DetailTransaction::whereBetween('created_at', [$from, $to])->latest()->get();

        // day
        // $data = DetailTransaction::whereDate('created_at', '=', date('2022-08-29'))->latest()->get();
        // $data = DetailTransaction::whereDay('created_at', '=', date('d'))->latest()->get(); // today

        // this month
        // $data = DetailTransaction::whereMonth('created_at', '=', date('m'))->latest()->get(); // this month

        // this year
        // $data = DetailTransaction::whereYear('created_at', '=', date('Y'))->latest()->get();

        // coba
        $query = Transaction::query();
        // $query->where('status', 1)
        //     ->whereIn('nama', ['PEMBELIAN', 'PENJUALAN']);
        $query->where(['nama' => request('nama'), 'status' => 1]);

        if (request('date') === 'hari') {
            if (request()->has('hari') && request('hari') !== null) {
                $query->whereDay('created_at', '=', request('hari'));
            } else {
                $query->whereDay('created_at', '=', date('d'));
            }
        } else if (request('date') === 'bulan') {
            if (request()->has('bulan') && request('bulan') !== null) {
                $query->whereMonth('created_at', '=', request('bulan'));
            } else {
                $query->whereMonth('created_at', '=', date('m'));
            }
        } else if (request('date') === 'tahun') {
            if (request()->has('tahun') && request('tahun') !== null) {
                $query->whereYear('created_at', '=', request('tahun'));
            } else {
                $query->whereYear('created_at', '=', date('Y'));
            }
        } else if (request('date') === 'spesifik') {
            $query->whereDate('created_at', '=', request('from'));
        } else {
            $query->whereBetween('created_at', [request('from'), request('to')]);
        }

        $data = $query->with('detail_transaction')->latest()->get();

        return TransactionResource::collection($data);

        // $produk = Product::paginate(10);
        // foreach ($produk as &$key) {
        //     $key->qty_old = [];
        //     $key->harga_old = [];
        //     $key->subtotal_old = [];
        // }
        // if (request('nama') === 'PEMBELIAN' || request('nama') === 'PENJUALAN') {
        //     foreach ($data as &$transaction) {
        //         foreach ($transaction->detail_transaction as &$value) {
        //             foreach ($produk as &$key) {
        //                 $qty_old = $key->qty_old;
        //                 $harga_old = $key->harga_old;
        //                 $subtotal_old = $key->subtotal_old;
        //                 if (request('nama') === 'PENJUALAN') {
        //                     if ($value->product_id === $key->id) {
        //                         if ($value->harga === $key->harga_jual_umum) {
        //                             $key->qty_umum += $value->qty;
        //                             $key->harga_umum = $value->harga;
        //                             $key->subtotal_umum += ($value->qty * $value->harga);
        //                         } else if ($value->harga === $key->harga_jual_resep) {
        //                             $key->qty_resep += $value->qty;
        //                             $key->harga_resep = $value->harga;
        //                             $key->subtotal_resep += ($value->qty * $value->harga);
        //                         } else if ($value->harga === $key->harga_jual_cust) {
        //                             $key->qty_cust += $value->qty;
        //                             $key->harga_cust = $value->harga;
        //                             $key->subtotal_cust += ($value->qty * $value->harga);
        //                         } else {
        //                             array_push($qty_old, $value->qty);
        //                             array_push($harga_old, $value->harga);
        //                             array_push($subtotal_old, ($value->qty * $value->harga));
        //                         }
        //                     }
        //                 }
        //                 if (request('nama') === 'PEMBELIAN') {
        //                     if ($value->product_id === $key->id) {
        //                         if ($value->harga === $key->harga_beli) {
        //                             $key->qty += $value->qty;
        //                             $key->harga = $value->harga;
        //                             $key->subtotal += ($value->qty * $value->harga);
        //                         } else {
        //                             array_push($qty_old, $value->qty);
        //                             array_push($harga_old, $value->harga);
        //                             array_push($subtotal_old, ($value->qty * $value->harga));
        //                         }
        //                     }
        //                 }
        //                 $key->qty_old = $qty_old;
        //                 $key->harga_old = $harga_old;
        //                 $key->subtotal_old = $subtotal_old;
        //             }
        //         }
        //     }
        // }

        // return ProductResource::collection($produk);
        // $kirim =  TransactionResource::collection($data);
        // return  TransactionResource::collection($data);
        // return response()->json([
        //     // 'bulan' => date('m') - 1,
        //     'product' => $produk,
        //     'data' => $data,
        //     // 'kirim' => $kirim
        // ]);


    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'transaction_id' => 'required',
                    'product_id' => 'required',
                    'qty' => 'required',
                    'harga' => 'required',
                    'sub_total' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                DetailTransaction::create($request->only(['transaction_id', 'product_id', 'qty', 'harga', 'sub_total']));
                // DetailTransaction::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data DetailTransaction {$user->name}");
            } else {
                $kategori = DetailTransaction::find($request->id);
                $kategori->update([
                    'transaction_id' => $request->transaction_id,
                    'product_id' => $request->product_id,
                    'qty' => $request->qty,
                    'harga' => $request->harga,
                    'sub_total' => $request->sub_total
                ]);

                // $auth->log("Merubah data DetailTransaction {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = DetailTransaction::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data DetailTransaction {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
