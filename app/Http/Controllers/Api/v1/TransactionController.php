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
    public function getById()
    {
        // $data = Transaction::paginate();
        $data = Transaction::find(request()->id)->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'reff' => 'required',
                    'faktur' => 'required',
                    'tanggal' => 'required',
                    'nama' => 'required',
                    'jenis' => 'required',
                    'total' => 'required',
                    'ongkir' => 'required',
                    'potongan' => 'required',
                    'bayar' => 'required',
                    'kembali' => 'required',
                    'tempo' => 'required',
                    'kasir_id' => 'required',
                    'supplier_id' => 'required',
                    'status' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Transaction::create($request->only([
                    'reff',
                    'faktur',
                    'tanggal',
                    'nama',
                    'jenis',
                    'total',
                    'ongkir',
                    'potongan',
                    'bayar',
                    'kembali',
                    'tempo',
                    'kasir_id',
                    'supplier_id',
                    'status',
                ]));
                // Transaction::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data Transaction {$user->name}");
            } else {
                $kategori = Transaction::find($request->id);
                $kategori->update([
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

                // $auth->log("Merubah data Transaction {$user->name}");
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
