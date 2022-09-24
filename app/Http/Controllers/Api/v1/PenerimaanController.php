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
            $query->whereBetween('tanggal', [$from, $to]);
        }
    }

    public function getByDate()
    {
        $periode = [];
        $query = DetailPenerimaan::query()->selectRaw('penerimaan_id, sum(sub_total) as sub_total');
        $query->whereHas('transaction', function ($gg) {
            $gg->where('nama', '=', request('nama'))
                ->where('status', '=', 1)
                ->when(request('customer_id'), function ($sp) {
                    return $sp->where('customer_id', request('customer_id'));
                });
            $this->periode($gg, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);
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
