<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DetailPemesanan;
use App\Models\Pemesanan;
use App\Models\Perusahaan;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemesananController extends Controller
{
    //

    public static function nomoring($n)
    {

        $a = $n + 1;
        $has = null;
        $lbr = strlen($a);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return  $has  . $a . date('/d/m/Y');
    }
    public function getDraft()
    {
        $data = Pemesanan::where('flag', '1')
            ->with(
                'supplier',
                'detail',
                'detail.produk:id,kode_produk,nama,satuan_id',

            )
            ->first();
        return new JsonResponse($data);
    }
    public function getPerusahaan()
    {
        $data = Supplier::select('id', 'kode_supplier', 'nama')
            ->get();
        return new JsonResponse($data);
    }
    public function getProduk()
    {
        $data = Product::select('id', 'kode_produk', 'nama', 'satuan_id')
            ->with('satuan')
            ->where('nama', 'LIKE', '%' . request('q') . '%')
            ->limit(5)
            ->get();
        $data->append('stok');
        return new JsonResponse($data);
    }
    public function simpanProduk(Request $request)
    {

        try {
            DB::beginTransaction();
            $count = Pemesanan::whereBetween('tgl_pemesanan', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
                ->count();
            $nopemesanan = !$request->nopemesanan ? $this->nomoring($count) : $request->nopemesanan;

            $head = Pemesanan::updateOrCreate(
                [
                    'nopemesanan' => $nopemesanan,
                ],
                [
                    'tgl_pemesanan' => $request->tgl_pemesanan,
                    'kode_supplier' => $request->kode_supplier,
                    'flag' => '1',

                ]
            );
            $req = $request->all();
            if (!$head) {
                return new JsonResponse([
                    'message' => 'Gagal Simpan Header',
                    'nopemesanan' => $nopemesanan,
                    'req' => $req,
                ], 410);
            }
            $detail = DetailPemesanan::updateOrCreate(
                [
                    'nopemesanan' => $nopemesanan,
                    'kode_produk' => $request->kode_produk,
                ],
                [
                    'qty' => $request->qty,
                    'satuan' => $request->satuan,
                ]
            );
            if (!$detail) {
                return new JsonResponse([
                    'message' => 'Gagal Simpan Produk',
                    'nopemesanan' => $nopemesanan,
                    'req' => $req,
                ], 410);
            }
            $detail->load('produk:id,kode_produk,nama,satuan_id', 'produk.satuan');
            DB::commit();
            return new JsonResponse([
                'message' => 'Produk sudah disimpan',
                'nopemesanan' => $nopemesanan,
                'detail' => $detail,
                'req' => $req,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $th,
                'request' => $request->all(),
                // 'nodistribusi' => $nodistribusi,
            ], 410);
        }
    }
    public function hapusProduk(Request $request)
    {
        $data = DetailPemesanan::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan, tidak ada yang dihapus'
            ], 410);
        }
        $data->delete();

        $count = DetailPemesanan::where('nopemesanan', $request->nopemesanan)->get()->count();
        if ((int) $count <= 0) {
            $head = Pemesanan::where('nopemesanan', $request->nopemesanan)->first();
            if ($head) $head->delete();
        }

        return new JsonResponse([
            'message' => 'Produk sudah dihapus',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function selesaiPemesanan(Request $request)
    {

        $data = Pemesanan::where('nopemesanan', $request->nopemesanan)->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Tidak bisa Kunci, data tidak ditemukan'
            ], 410);
        }
        $data->update([
            'flag' => '2'
        ]);
        return new JsonResponse([
            'message' => 'Data Berhasil dikunci'
        ]);
    }
    public function getList()
    {
        $raw = Pemesanan::where('nopemesanan', 'LIKE', '%' . request('q') . '%')
            ->with(
                'supplier',
                'detail',
                'detail.produk:id,kode_produk,nama',
            )
            ->where('flag', '!=', '1')
            ->orderBy('id', 'DESC')
            ->paginate(request('per_page'));
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
        ]);
    }
    public function bukaKunci(Request $request)
    {
        $count = Pemesanan::where('flag', '1')->get()->count();
        if ((int)$count > 0) {
            return new JsonResponse([
                'message' => 'Masih ada pemesanan yang belum dikunci, Hanya boleh ada satu draft dalam satu waktu',
                'req' => $request->all()
            ], 410);
        }
        $data = Pemesanan::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Kunci Tidak dibuka, Pemesanan tidak ditemuka',
                'req' => $request->all()
            ], 410);
        }
        $data->update(['flag' => '1']);

        return new JsonResponse([
            'message' => 'Kunci Sudah dibuka',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
}
