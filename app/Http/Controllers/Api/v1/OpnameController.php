<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StokOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpnameController extends Controller
{
    //
    public function getOpname()
    {
        $kode = [];
        if (request('q')) {
            $kode = Product::select('kode_produk')
                ->where('nama', 'like', '%' . request('q') . '%')
                ->get();
        }
        $raw = StokOpname::when(sizeof($kode) > 0, function ($query) use ($kode) {
            $query->whereIn('kode_produk', $kode);
        })
            ->with('product:id,kode_produk,nama')
            ->orderBy('tgl_opname', 'DESC')
            ->orderBy('product_id', 'ASC')
            ->paginate(request('per_page'));
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
        ]);
    }
    public function store()
    {
        $tglOpnameTerakhir = StokOpname::select('tgl_opname')->orderBy('tgl_opname', 'desc')->first();
        if ($tglOpnameTerakhir) {
            $tgl = date('Y-m-d', strtotime($tglOpnameTerakhir->tgl_opname));
            $today = date('Y-m-d');
            $dToday = date_create($today);
            $dOpname = date_create($tgl);
            $diff = date_diff($dToday, $dOpname);

            if ($diff->days == 0) {
                return new JsonResponse(['message' => 'Tidak Boleh Melakukan Opname di Hari yang sama'], 410);
            }
        }
        $data = Product::get();
        $data->append('stok');
        $inst = [];
        $tgl = date('Y-m-d H:i:s');
        // $tgl = date('2025-01-31 23:58:59'); // percobaan
        foreach ($data as $key) {
            $inst[] = [
                'product_id' => $key->id,
                'kode_produk' => $key->kode_produk,
                'jumlah' => $key->stok,
                'tgl_opname' => $tgl,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (sizeof($inst) <= 0) {
            return new JsonResponse(['message' => 'Data Gagal disimpan'], 410);
        }
        StokOpname::insert($inst);
        return new JsonResponse([
            // 'data' => $data,
            // 'inst' => $inst
            'message' => 'Data Opname Sudah disimpan'
        ], 200);
    }
}
