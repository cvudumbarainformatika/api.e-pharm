<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\DokterResource;
use App\Models\Cabang;
use App\Models\Dokter;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DokterController extends Controller
{
    public function index()
    {
        // $data = Dokter::paginate();
        $data = Dokter::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return DokterResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        // validasi cabang utama strt
        // $me = Info::first();
        // if ($me->kodecabang != 'APS0001') {
        //     return new JsonResponse(['message' => 'Edit, Tambah master hanya dilakukan di cabang utama'], 410);
        // }
        // validasi cabang utama end
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required',
                    // 'alamat' => 'required',
                    // 'kontak' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                $dokter = Dokter::create($request->only(['nama', 'alamat', 'kontak']));
                if ($dokter->kode_dokter === null) {
                    $kode = NumberHelper::setNumber($dokter->id, 'DKT');
                    // $kode = AutogeneratorController::setNumber($dokter->id, 'DKT');
                    $dokter->update([
                        'kode_dokter' => $kode
                    ]);
                }
                // Dokter::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data Dokter {$user->name}");
            } else {
                $dokter = Dokter::find($request->id);
                $dokter->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'kontak' => $request->kontak,
                ]);

                // $auth->log("Merubah data Dokter {$user->name}");
            }
            // pots notif start
            // $cabang = Cabang::pluck('kodecabang')->toArray();
            // $ind = array_search($me->kodecabang, $cabang);
            // $anu = $cabang;
            // unset($anu[$ind]);
            // foreach ($anu as $key) {
            //     $msg = [
            //         'sender' => $me->kodecabang,
            //         'receiver' => $key,
            //         'type' => 'update master',
            //         'model' => 'Dokter',
            //         'content' => $dokter,
            //     ];

            //     $response = CloudHelper::post_cloud($msg);
            // }
            // pots notif end
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
        // validasi cabang utama strt
        $me = Info::first();
        if ($me->kodecabang != 'APS0001') {
            return new JsonResponse(['message' => 'Edit, Tambah master hanya dilakukan di cabang utama'], 410);
        }
        // validasi cabang utama end
        try {

            DB::beginTransaction();
            $id = $request->id;

            $data = Dokter::find($id);
            $del = $data->delete();

            if (!$del) {
                return response()->json([
                    'message' => 'Error on Delete'
                ], 500);
            }
            // pots notif start
            $cabang = Cabang::pluck('kodecabang')->toArray();
            $ind = array_search($me->kodecabang, $cabang);
            $anu = $cabang;
            unset($anu[$ind]);
            foreach ($anu as $key) {
                $msg = [
                    'sender' => $me->kodecabang,
                    'receiver' => $key,
                    'type' => 'update master',
                    'model' => 'Dokter',
                    'content' => $data,
                ];

                $response = CloudHelper::post_cloud($msg);
            }
            // pots notif end
            DB::commit();

            // $user->log("Menghapus Data Dokter {$data->nama}");
            return response()->json([
                'message' => 'Data sukses terhapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
