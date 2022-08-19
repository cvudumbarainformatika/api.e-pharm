<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index()
    {
        // $data = Supplier::paginate();
        $data = Supplier::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $data->load('perusahaan:id,nama');
        return SupplierResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required',
                    'alamat' => 'required',
                    'perusahaan_id' => 'required',
                    'kontak' => 'required',
                    'saldo_awal_hutang' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Supplier::create($request->only(['nama', 'alamat', 'perusahaan_id', 'kontak', 'saldo_awal_hutang']));
                // Supplierlier::create([
                //     'Supplierma' => $request->name
                // ]);

                // $auth->log("Memasukkan data Supplier {$user->name}");
            } else {
                $kategori = Supplier::find($request->id);
                $kategori->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'perusahaan_id' => $request->perusahaan_id,
                    'kontak' => $request->kontak,
                    'saldo_awal_hutang' => $request->saldo_awal_hutang
                ]);

                // $auth->log("Merubah data Supplier {$user->name}");
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

        $data = Supplier::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Supplier {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
