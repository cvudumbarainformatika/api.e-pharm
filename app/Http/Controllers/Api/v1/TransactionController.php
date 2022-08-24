<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        // $data = Transaction::paginate();
        $data = Transaction::orderBy(request(('order_by'), request('sort')))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function withDetail()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['reff' => request()->reff])->with('detail_transaction')->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function withBeban()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['reff' => request()->reff])->with('beban_transaction')->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        $simpan = '';
        try {
            $data = '';

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'reff' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                $data = Transaction::updateOrCreate([
                    'reff' => $request->reff,
                ]);
                $simpan = $data;
                if ($request->nama === 'BEBAN') {
                    $data->beban_transaction->updateOrCreate([
                        'beban_id' => $request->beban_id
                    ]);
                } else {
                    $data->detail_transaction->updateOrCreate([
                        'product_id' => $request->product_id
                    ]);
                }
                // Transaction::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data Transaction {$user->name}");
            } else {
                $transaction = Transaction::find($request->id);
                $transaction->update([
                    'reff' => $request->reff,
                    'faktur' => $request->faktur,
                    'tanggal' => $request->tanggal,
                    'nama' => $request->nama,
                    'jenis' => $request->jenis,
                    'total' => $request->total,
                    'ongkir' => $request->ongkir,
                    'potongan' => $request->potongan,
                    'bayar' => $request->bayar,
                    'kembali' => $request->kembali,
                    'tempo' => $request->tempo,
                    'supplier_id' => $request->supplier_id,
                    'kasir_id' => $request->kasir_id,
                    'status' => $request->status,
                ]);
                $data = $transaction;
                // $auth->log("Merubah data Transaction {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success', 'data' => $data], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'simpan' => $simpan], 500);
        }
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Transaction::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Transaction {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
