<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Setting\Info;
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
    }
    public function allCabang()
    {
        $data = Cabang::get();
        return new JsonResponse($data);
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
            //         'model' => 'Cabang',
            //         'content' => $kategori,
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
