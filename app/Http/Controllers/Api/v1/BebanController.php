<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BebanResource;
use App\Models\Beban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BebanController extends Controller
{
    public function index()
    {
        // $data = Beban::paginate();
        $data = Beban::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return BebanResource::collection($data);
    }
    public function beban()
    {
        $data = Beban::latest()->paginate(request('per_page'));
        return BebanResource::collection($data);
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

                // Beban::create($request->only('nama'));
                Beban::firstOrCreate([
                    'nama' => $request->nama
                ]);

                // $auth->log("Memasukkan data Beban {$user->name}");
            } else {
                $kategori = Beban::find($request->id);
                $kategori->update([
                    'nama' => $request->nama
                ]);

                // $auth->log("Merubah data Beban {$user->name}");
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

        $data = Beban::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Beban {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
