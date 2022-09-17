<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PenerimaanResource;
use App\Models\DetailPenerimaan;
use App\Models\Penerimaan;
use Illuminate\Http\JsonResponse;
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


    public function getByDate()
    {
        $periode = [];
        $query = DetailPenerimaan::query()->selectRaw('penerimaan_id, sum(sub_total) as sub_total');
        $query->whereHas('transaction', function ($gg) {
            $gg->where(['nama' => request('nama'), 'status' => 1]);

            if (request('date') === 'hari') {
                if (request()->has('hari') && request('hari') !== null) {
                    $gg->whereDay('tanggal', '=', request('hari'));
                } else {
                    $gg->whereDay('tanggal', '=', date('d'));
                }
            } else if (request('date') === 'bulan') {
                if (request()->has('bulan') && request('bulan') !== null) {
                    $gg->whereMonth('tanggal', '=', request('bulan'));
                } else {
                    $gg->whereMonth('tanggal', '=', date('m'));
                }
            } else if (request('date') === 'spesifik') {
                $gg->whereDate('tanggal', '=', request('from'));
            } else {
                $gg->whereBetween('tanggal', [request('from'), request('to')]);
            }
        });


        $data = $query->groupBy('penerimaan_id')
            ->with(['penerimaan'])
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
