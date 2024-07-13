<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CustomerResource;
use App\Models\Customer;
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
                $kategori = Customer::find($request->id);
                $kategori->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'kontak' => $request->kontak,
                    'saldo_awal_piutang' => $request->saldo_awal_piutang
                ]);

                // $auth->log("Merubah data Customer {$user->name}");
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

        $data = Customer::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data customer {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
