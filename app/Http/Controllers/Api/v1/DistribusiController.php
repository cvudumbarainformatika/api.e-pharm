<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DistribusiAntarToko;
use App\Models\HeaderDistribusi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{
    public function getList()
    {
        $data = HeaderDistribusi::with(
            'details.produk.satuan',
            'asal',
            'menuju',
        )
            ->where('status', '>', 1)
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function getNodistDraft()
    {
        $data = HeaderDistribusi::with('details.produk.satuan')->where('status', 1)->first();
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $count = HeaderDistribusi::whereBetween('tgl_distribusi', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
                ->count();
            $nodistribusi = !$request->nodistribusi ? $this->nomoring($count) : $request->nodistribusi;
            $data = HeaderDistribusi::updateOrCreate(
                [
                    'nodistribusi' => $nodistribusi

                ],
                [
                    'pengirim' => $request->pengirim,
                    'dari' => $request->dari,
                    'tujuan' => $request->tujuan,
                    'penerima' => $request->penerima,
                    'tgl_permintaan' => $request->tgl_permintaan,
                    // 'tgl_distribusi' => $request->tgl_distribusi,
                    // 'tgl_terima' => $request->tgl_terima,
                ]
            );
            $detail = DistribusiAntarToko::updateOrCreate(
                [
                    'nodistribusi' => $nodistribusi,
                    'kode_produk' => $request->kode_produk,
                    'product_id' => $request->product_id,
                ],
                [
                    'jumlah' => $request->jumlah,
                    // 'harga' => $request->harga,
                    // 'subtotal' => $request->subtotal,
                    'expired' => $request->expired,

                ]
            );

            $detail->load('produk.satuan');
            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'detail' => $detail,
                'nodistribusi' => $nodistribusi,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $th,
                'request' => $request->all()
            ], 500);
        }
    }
    public static function nomoring($n)
    {
        $a = $n + 1;
        $has = null;
        $lbr = strlen($a);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return date('dmY') . $has  . $a;
    }
    public function daleteItem(Request $request)
    {
        $det = DistribusiAntarToko::find($request->id);
        if (!$det) {
            return new JsonResponse([
                'message' => 'Data Tidak ditemukan'
            ], 410);
        }
        $det->delete();
        $allDet = DistribusiAntarToko::where('nodistribusi', $request->nodistribusi)->count();
        if ($allDet === 0) {
            $head = HeaderDistribusi::where('nodistribusi', $request->nodistribusi)->where('status', 1)->first();
            if ($head) $head->delete();
        }
        return new JsonResponse([
            'message' => 'Data Dihapus',
            'data' => $det,
            'allDet' => $allDet
        ]);
    }
    public function selesai(Request $request)
    {

        $head = HeaderDistribusi::where('nodistribusi', $request->nodistribusi)->where('status', 1)->first();
        if (!$head) {
            return new JsonResponse([
                'message' => 'Data Tidak ditemukan'
            ], 410);
        }
        $head->update(['status' => 2]);
        return new JsonResponse([
            'message' => 'Data Tidak ditemukan',
            'data' => $head,
        ]);
    }
    public function distribusi(Request $request)
    {
        // $user = auth()->user();
        // return new JsonResponse([
        //     'message' => 'Data Tidak Ditemukan',
        //     'user' => $user
        // ], 410);
        try {
            DB::beginTransaction();
            $data = HeaderDistribusi::find($request->id);
            if (!$data) {
                return new JsonResponse([
                    'message' => 'Data Tidak Ditemukan',
                ], 410);
            }
            $det = $request->details;
            if (count($det) > 0) {
                foreach ($det as $key) {
                    $ada = DistribusiAntarToko::find($key['id']);
                    if (!$ada) {
                        return new JsonResponse([
                            'message' => 'Rinci Tidak Ditemukan',
                            'key' => $key
                        ], 410);
                    }
                    $ada->update([
                        'qty' => $key['qty']
                    ]);
                }
            }
            $user = auth()->user();
            $data->update([
                'tgl_distribusi' => $request->tgl_distribusi,
                'pengirim' => $user->name,
                'status' => 3,
            ]);
            DB::commit();
            return new JsonResponse([
                'message' => 'Sudah Didistribusikan',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan ' . $th,
                'error' => $th,
                'request' => $request->all()
            ], 500);
        }
    }
}
