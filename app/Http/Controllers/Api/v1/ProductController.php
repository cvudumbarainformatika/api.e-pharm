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
            ->with('rak:id,nama', 'merk:id,nama', 'satuan:id,nama', 'satuanBesar:id,nama', 'kategori:id,nama')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // $data->load('kategori:id,nama');
        // $data->load('satuan:id,nama');
        // $data->load('rak:id,nama');
        // $data->load('merk:id,nama');
        return ProductResource::collection($data);
    }

    public function produk()
    {
        $data = Product::latest()->paginate(request('per_page'));
        return ProductResource::collection($data);
    }
    public function allProduk()
    {
        $data = Product::latest()->get();
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
                    'pengali' => 'required',
                    'satuan_id' => 'required',
                    'harga_beli' => 'required',
                    'harga_jual_umum' => 'required',
                    'harga_jual_resep' => 'required',
                    'harga_jual_cust' => 'required',
                    'limit_stok' => 'required',
                    'rak_id' => 'required',
                    'kategori_id' => 'required',
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Product::create($request->only([
                    'nama',
                    'barcode',
                    'merk_id',
                    'satuan_id',
                    'pengali',
                    'satuan_besar_id',
                    'harga_beli',
                    'harga_jual_umum',
                    'harga_jual_resep',
                    'harga_jual_cust',
                    'stok_awal',
                    'limit_stok',
                    'rak_id',
                    'kategori_id'
                ]));
                // Product::create([
                //     'nama' => $request->name
                // ]);

                // $auth->log("Memasukkan data Product {$user->name}");
            } else {
                $kategori = Product::find($request->id);
                $kategori->update($request->only(
                    'barcode',
                    'nama',
                    'merk_id',
                    'satuan_id',
                    'pengali',
                    'satuan_besar_id',
                    'harga_beli',
                    'harga_jual_umum',
                    'harga_jual_resep',
                    'harga_jual_cust',
                    'stok_awal',
                    'limit_stok',
                    'rak_id',
                    'kategori_id',
                ));

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
