<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        // $data = Product::paginate();
        $data = Product::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $data->load('kategori:id,nama');
        $data->load('satuan:id,nama');
        $data->load('rak:id,nama');
        $data->load('merk:id,nama');
        return ProductResource::collection($data);
    }

    public function produk()
    {
        $data = Product::latest()->paginate(request('per_page'));
        return ProductResource::collection($data);
    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'barcode' => 'required',
                    'nama' => 'required',
                    'merk_id' => 'required',
                    'satuan_id' => 'required',
                    'harga_beli' => 'required',
                    'harga_jual_umum' => 'required',
                    'harga_jual_resep' => 'required',
                    'harga_jual_cust' => 'required',
                    'stok_awal' => 'required',
                    'limit_stok' => 'required',
                    'rak_id' => 'required',
                    'kategori_id' => 'required',
                    'expired' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Product::create($request->only(['nama', 'barcode', 'merk_id', 'satuan_id', 'harga_beli', 'harga_jual_umum', 'harga_jual_resep', 'harga_jual_cust', 'stok_awal', 'limit_stok', 'rak_id', 'kategori_id', 'expired']));
                // Product::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data Product {$user->name}");
            } else {
                $kategori = Product::find($request->id);
                $kategori->update([
                    'barcode' => $request->barcode,
                    'nama' => $request->nama,
                    'merk_id' => $request->merk_id,
                    'satuan_id' => $request->satuan_id,
                    'harga_beli' => $request->harga_beli,
                    'harga_jual_umum' => $request->harga_jual_umum,
                    'harga_jual_resep' => $request->harga_jual_resep,
                    'harga_jual_cust' => $request->harga_jual_cust,
                    'stok_awal' => $request->stok_awal,
                    'limit_stok' => $request->limit_stok,
                    'rak_id' => $request->rak_id,
                    'kategori_id' => $request->kategori_id,
                    'expired' => $request->expired,
                ]);

                // $auth->log("Merubah data Product {$user->name}");
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

        $data = Product::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Product {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
