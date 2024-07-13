<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BebanResource;
use App\Models\Beban;
use App\Models\BebanTransaction;
use Illuminate\Http\JsonResponse;
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
        $data = Beban::latest()->get();
        return BebanResource::collection($data);
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
        $query = BebanTransaction::query()->selectRaw('beban_id, sum(sub_total) as sub_total');
        $query->whereHas('transaction', function ($gg) {
            $gg->where('nama', '=', request('nama'))
                ->where('status', '=', 2)
                ->when(request('supplier_id'), function ($sp) {
                    return $sp->where('supplier_id', request('supplier_id'));
                });
            $this->periode($gg, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);
        });


        $data = $query->groupBy('beban_id')
            ->with(['beban'])
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

                // Beban::create($request->only('nama'));
                $beban = Beban::firstOrCreate([
                    'nama' => $request->nama
                ]);
                if ($beban->kode_beban === null) {
                    $kode = NumberHelper::setNumber($beban->id, 'BBN');
                    // $kode = AutogeneratorController::setNumber($beban->id, 'BBN');
                    $beban->update([
                        'kode_beban' => $kode
                    ]);
                }

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
