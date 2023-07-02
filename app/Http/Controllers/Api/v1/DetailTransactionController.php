<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\DetailTransactionResource;
use App\Http\Resources\v1\ProductResource;
use App\Http\Resources\v1\TransactionResource;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

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
            $query->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }
    }

    public function getByDate()
    {
        // ambil detail transaction
        $query = DetailTransaction::query()->selectRaw('product_id, harga, sum(qty) as jml');
        $query->whereHas('transaction', function ($gg) {
            $gg->where('nama', '=', request('nama'))
                ->whereIn('status', [2, 3])
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
            $this->periode($gg, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);
        });
        $data = $query->groupBy('product_id', 'harga')
            ->with(['product'])
            ->get();

        return new JsonResponse($data);
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
