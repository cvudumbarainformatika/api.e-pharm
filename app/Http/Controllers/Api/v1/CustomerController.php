<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CustomerResource;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index()
    {
        // $data = Customer::paginate();
        $data = Customer::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return CustomerResource::collection($data);
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
                    // 'kontak' => 'required',
                    // 'saldo_awal_piutang' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Customer::create($request->only('nama'));
                $cust = Customer::firstOrCreate([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'kontak' => $request->kontak,
                    'saldo_awal_piutang' => $request->saldo_awal_piutang
                ]);
                if ($cust->kode_customer === null) {
                    $kode = NumberHelper::setNumber($cust->id, 'CST');
                    // $kode = AutogeneratorController::setNumber($cust->id, 'CST');
                    $cust->update([
                        'kode_customer' => $kode
                    ]);
                }

                // $auth->log("Memasukkan data Customer {$user->name}");
            } else {
                $cust = Customer::find($request->id);
                $cust->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'kontak' => $request->kontak,
                    'saldo_awal_piutang' => $request->saldo_awal_piutang
                ]);

                // $auth->log("Merubah data Customer {$user->name}");
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
            //         'model' => 'Customer',
            //         'content' => $cust,
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

            $data = Customer::find($id);
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
                    'model' => 'Customer',
                    'content' => $data,
                ];

                $response = CloudHelper::post_cloud($msg);
            }
            // pots notif end

            DB::commit();
            // $user->log("Menghapus Data customer {$data->nama}");
            return response()->json([
                'message' => 'Data sukses terhapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
