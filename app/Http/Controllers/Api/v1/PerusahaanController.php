<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PerusahaanResource;
use App\Models\Cabang;
use App\Models\Perusahaan;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerusahaanController extends Controller
{
    public function index()
    {
        // $data = Perusahaan::paginate();
        $data = Perusahaan::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return PerusahaanResource::collection($data);
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
                    'nama' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Perusahaan::create($request->only('nama'));
                $kategori = Perusahaan::firstOrCreate([
                    'nama' => $request->nama
                ]);
                if ($kategori->kode_beban === null) {
                    $kode = NumberHelper::setNumber($kategori->id, 'CMP');
                    // $kode = AutogeneratorController::setNumber($kategori->id, 'BBN');
                    $kategori->update([
                        'kode' => $kode
                    ]);
                }

                // $auth->log("Memasukkan data Perusahaan {$user->name}");
            } else {
                $kategori = Perusahaan::find($request->id);
                $kategori->update([
                    'nama' => $request->nama
                ]);

                // $auth->log("Merubah data Perusahaan {$user->name}");
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
            //         'model' => 'Perusahaan',
            //         'content' => $kategori,
            //     ];

            //     $response = CloudHelper::post_cloud($msg);
            // }
            // pots notif end

            DB::commit();
            return response()->json(['message' => 'success', 'data' => $kategori], 201);
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

            $data = Perusahaan::find($id);
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
                    'type' => 'delete master',
                    'model' => 'Perusahaan',
                    'content' => $data,
                ];

                $response = CloudHelper::post_cloud($msg);
            }
            // pots notif end

            DB::commit();
            // $user->log("Menghapus Data Kategori {$data->nama}");
            return response()->json([
                'message' => 'Data sukses terhapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
