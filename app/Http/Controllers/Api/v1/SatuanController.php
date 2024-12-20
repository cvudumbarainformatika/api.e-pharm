<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SatuanResource;
use App\Models\Cabang;
use App\Models\Satuan;
use App\Models\SatuanBesar;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SatuanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $data = Satuan::paginate();
        $data = Satuan::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return SatuanResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $auth = $request->user();
        // $me = Info::first();
        // if ($me->kodecabang != 'APS0001') {
        //     return new JsonResponse(['message' => 'Edit, Tambah master hanya dilakukan di cabang utama'], 410);
        // }
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Satuan::create($request->only('name'));
                $satuan = Satuan::firstOrCreate([
                    'nama' => $request->nama
                ]);
                if ($satuan->kode_satuan === null) {
                    $kode = AutogeneratorController::setNumber($satuan->id, 'STK');
                    $satuan->update([
                        'kode_satuan' => $kode
                    ]);
                }

                // $auth->log("Memasukkan data satuan {$user->name}");
            } else {
                $satuan = Satuan::find($request->id);
                $satuan->update([
                    'nama' => $request->nama
                ]);
                // return response()->json(['satuan' => $satuan, 'data' => $request->all()]);
                // $satuan->name = $request->name;
                // $satuan->save();

                // $auth->log("Merubah data Satuan {$user->name}");
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
            //         'model' => 'Satuan',
            //         'content' => $satuan,
            //     ];

            //     $response = CloudHelper::post_cloud($msg);
            // }
            // pots notif end
            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ada Kesalahan', 'error' => $e], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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

            $data = Satuan::find($id);
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
                    'model' => 'Satuan',
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexBesar()
    {
        // $data = SatuanBesar::paginate();
        $data = SatuanBesar::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return SatuanResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeBesar(Request $request)
    {
        // $auth = $request->user();
        // $me = Info::first();
        // if ($me->kodecabang != 'APS0001') {
        //     return new JsonResponse(['message' => 'Edit, Tambah master hanya dilakukan di cabang utama'], 410);
        // }
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // SatuanBesar::create($request->only('name'));
                $satuan = SatuanBesar::firstOrCreate([
                    'nama' => $request->nama
                ]);
                if ($satuan->kode_satuan === null) {
                    $kode = NumberHelper::setNumber($satuan->id, 'STB');
                    // $kode = AutogeneratorController::setNumber($satuan->id, 'STB');
                    $satuan->update([
                        'kode_satuan' => $kode
                    ]);
                }

                // $auth->log("Memasukkan data satuan {$user->name}");
            } else {
                $satuan = SatuanBesar::find($request->id);
                $satuan->update([
                    'nama' => $request->nama
                ]);
                // return response()->json(['satuan' => $satuan, 'data' => $request->all()]);
                // $satuan->name = $request->name;
                // $satuan->save();

                // $auth->log("Merubah data SatuanBesar {$user->name}");
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
            //         'model' => 'SatuanBesar',
            //         'content' => $satuan,
            //     ];

            //     $response = CloudHelper::post_cloud($msg);
            // }
            // pots notif end

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ada Kesalahan', 'error' => $e], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyBesar(Request $request)
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

            $data = SatuanBesar::find($id);
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
                    'model' => 'SatuanBesar',
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
