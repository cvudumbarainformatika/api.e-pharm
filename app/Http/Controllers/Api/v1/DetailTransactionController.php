<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\DetailTransactionResource;
use App\Models\DetailTransaction;
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
