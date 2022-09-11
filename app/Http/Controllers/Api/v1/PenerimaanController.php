<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PenerimaanResource;
use App\Models\Penerimaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenerimaanController extends Controller
{
    public function index()
    {
        // $data = Penerimaan::paginate();
        $data = Penerimaan::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return PenerimaanResource::collection($data);
    }
    public function penerimaan()
    {
        $data = Penerimaan::latest()->paginate(request('per_page'));
        return PenerimaanResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Penerimaan::create($request->only('nama'));
                Penerimaan::firstOrCreate([
                    'nama' => $request->nama
                ]);

                // $auth->log("Memasukkan data Penerimaan {$user->name}");
            } else {
                $kategori = Penerimaan::find($request->id);
                $kategori->update([
                    'nama' => $request->nama
                ]);

                // $auth->log("Merubah data Penerimaan {$user->name}");
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

        $data = Penerimaan::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Penerimaan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
