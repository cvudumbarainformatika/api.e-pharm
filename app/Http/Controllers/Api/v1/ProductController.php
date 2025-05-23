<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Helpers\NumberHelper;
use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ProductResource;
use App\Models\Cabang;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        // $data = Product::paginate();
        $data = Product::orderBy(request('order_by'), request('sort'))
            ->with('rak:id,nama', 'merk:id,nama', 'satuan:id,nama', 'satuanBesar:id,nama', 'kategori:id,nama', 'ada:id,product_id')
            ->filter(request(['q', 'rak_id']))
            // ->attributes('stok')
            ->paginate(request('per_page'));
        // $data->append('stok')->toArray();
        $data->append('stok');
        // $data->load('kategori:id,nama');
        // $data->load('satuan:id,nama');
        // $data->load('rak:id,nama');
        // $data->load('merk:id,nama');
        return ProductResource::collection($data);
    }
    public function getForPembelian()
    {
        // $data = Product::paginate();
        $data = Product::orderBy(request('order_by'), request('sort'))
            ->with('rak:id,nama', 'merk:id,nama', 'satuan:id,nama', 'satuanBesar:id,nama', 'kategori:id,nama')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $data->append('stok');

        return new JsonResponse($data);
    }

    public function produk()
    {
        $data = Product::latest()->paginate(request('per_page'));
        return ProductResource::collection($data);
    }
    public function allProduk()
    {
        $data = Product::latest()->get();
        return new JsonResponse($data);
        // return ProductResource::collection($data);
    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        // validasi cabang utama strt
        $me = Info::first();
        if ($me->kodecabang != 'APS0001') {
            return new JsonResponse(['message' => 'Edit, Tambah master hanya dilakukan di cabang utama'], 410);
        }
        // validasi cabang utama end
        try {

            DB::beginTransaction();

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

            $data = Product::updateOrCreate([
                'id' => $request->id
            ], $request->all());

            if ($data->kode_produk === null) {
                $kode = NumberHelper::setNumber($data->id, 'PRD');
                // $kode = AutogeneratorController::setNumber($data->id, 'PRD');
                $data->update([
                    'kode_produk' => $kode
                ]);
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
                    'model' => 'Product',
                    'content' => $data,
                ];

                $response = CloudHelper::post_cloud($msg);
            }
            // pots notif end
            // if (!$request->has('id')) {


            //     // Product::create($request->only([
            //     //     'nama',
            //     //     'barcode',
            //     //     'merk_id',
            //     //     'satuan_id',
            //     //     'pengali',
            //     //     'satuan_besar_id',
            //     //     'harga_beli',
            //     //     'harga_jual_umum',
            //     //     'harga_jual_resep',
            //     //     'harga_jual_cust',
            //     //     'stok_awal',
            //     //     'limit_stok',
            //     //     'rak_id',
            //     //     'kategori_id'
            //     // ]));
            //     // Product::create([
            //     //     'nama' => $request->name
            //     // ]);
            //     Product::create($request->all());

            //     // $auth->log("Memasukkan data Product {$user->name}");
            // } else {
            //     $kategori = Product::find($request->id);
            //     $kategori->update($request->only(
            //         'barcode',
            //         'nama',
            //         'merk_id',
            //         'satuan_id',
            //         'pengali',
            //         'satuan_besar_id',
            //         'harga_beli',
            //         'harga_jual_umum',
            //         'harga_jual_resep',
            //         'harga_jual_cust',
            //         'stok_awal',
            //         'limit_stok',
            //         'rak_id',
            //         'kategori_id',
            //     ));

            //     // $auth->log("Merubah data Product {$user->name}");
            // }

            DB::commit();
            if (!$data->wasRecentlyCreated) {
                $status = 200;
                $pesan = 'Data telah di perbarui';
            } else {
                $status = 201;
                $pesan = 'Data telah di tambakan';
            }
            return new JsonResponse(['message' => $pesan], $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;

        // validasi cabang utama strt
        // $me = Info::first();
        // if ($me->kodecabang != 'APS0001') {
        //     return new JsonResponse(['message' => 'Hapus master hanya dilakukan di cabang utama'], 410);
        // }
        // validasi cabang utama end
        try {

            DB::beginTransaction();
            $id = $request->id;

            $data = Product::find($id);
            // cari di detail transaksi, jika ada jangan di delete
            // $ada = DetailTransaction::where('product_id', $data->id)->first();
            // if ($ada) {
            //     return response()->json([
            //         'message' => 'Data sudah ada Di transaksi, jika dihapus akan mempengaruhi laporan.'
            //     ], 500);
            // }
            $del = $data->delete();

            if (!$del) {
                return response()->json([
                    'message' => 'Error on Delete'
                ], 500);
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
            //         'type' => 'delete master',
            //         'model' => 'Product',
            //         'content' => $data,
            //     ];

            //     $response = CloudHelper::post_cloud($msg);
            // }
            // pots notif end
            DB::commit();
            // $user->log("Menghapus Data Product {$data->nama}");
            return response()->json([
                'message' => 'Data sukses terhapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
