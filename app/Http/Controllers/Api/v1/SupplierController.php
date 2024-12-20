<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SupplierResource;
use App\Models\Cabang;
use App\Models\Setting\Info;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
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
                    'perusahaan_id' => 'required',
                    // 'kontak' => 'required',
                    // 'saldo_awal_hutang' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Supplier::create($request->only(['nama', 'alamat', 'perusahaan_id', 'kontak', 'saldo_awal_hutang']));
                $supp = Supplier::create($request->all());
                if ($supp->kode_supplier === null) {
                    $kode = NumberHelper::setNumber($supp->id, 'SUP');
                    // $kode = AutogeneratorController::setNumber($supp->id, 'SUP');
                    $supp->update([
                        'kode_supplier' => $kode
                    ]);
                }
                // Supplierlier::create([
                //     'Supplierma' => $request->name
                // ]);

                // $auth->log("Memasukkan data Supplier {$user->name}");
            } else {
                $supp = Supplier::find($request->id);
                $supp->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'perusahaan_id' => $request->perusahaan_id,
                    'kontak' => $request->kontak,
                    'saldo_awal_hutang' => $request->saldo_awal_hutang
                ]);

                // $auth->log("Merubah data Supplier {$user->name}");
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
            //         'model' => 'Supplier',
            //         'content' => $supp,
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
        // validasi cabang utama
        try {

            DB::beginTransaction();
            $id = $request->id;

            $data = Supplier::find($id);
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
                    'model' => 'Supplier',
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
