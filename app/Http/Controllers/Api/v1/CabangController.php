<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CabangController extends Controller
{
    //
    public function index()
    {
        // $data = Merk::paginate();
        $raw = Cabang::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $col = collect($raw);
        $data['data'] = $col['data'];
        $data['meta'] = collect($raw)->except('data');
        return new JsonResponse($data);
        // return MerkResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'namacabang' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Merk::create($request->only('namacabang'));
                Cabang::firstOrCreate([
                    'namacabang' => $request->namacabang
                ]);

                // $auth->log("Memasukkan data Cabang {$user->name}");
            } else {
                $kategori = Cabang::find($request->id);
                $kategori->update([
                    'namacabang' => $request->namacabang
                ]);

                // $auth->log("Merubah data Merk {$user->name}");
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

        return response()->json([
            'message' => 'Hapus Cabang tidak diijinkan'
        ], 500);
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Cabang::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Kategori {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
