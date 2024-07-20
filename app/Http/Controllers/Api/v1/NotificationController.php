<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Http\Controllers\Controller;
use App\Models\DistribusiAntarToko;
use App\Models\HeaderDistribusi;
use App\Models\Product;
use App\Models\Setting\Info;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function read(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->content;
            $msg = 'Notifikasi sudah dibaca dan dilaksanan';
            if ($request->model === 'HeaderDistribusi') {
                // cek status
                $head = $data;
                $stat = HeaderDistribusi::where('nodistribusi', $head['nodistribusi'])->first();
                $myStat = $stat->status ?? 0;
                // jaga-jaga ada kirimian 2 kali, jadi cek statusnya dulu... kalo sama ya ga usah di apa2ian..
                if ($myStat < $head['status']) {
                    $det = $data['details'];
                    unset($head['details']);
                    if (count($det) > 0) {
                        foreach ($det as $key) {
                            $prod = Product::select('id', 'kode_produk', 'nama')->where('kode_produk', $key['kode_produk'])->first();
                            $key['product_id'] = $prod->id;
                            // return new JsonResponse($key['nodistribusi']);

                            $dist = DistribusiAntarToko::updateOrCreate([
                                'nodistribusi' => $key['nodistribusi'],
                                'product_id' => $key['product_id'],
                            ], $key);
                        }
                    }
                    if ($request->type == 'kiriman distribusi') $head['tgl_terima'] = date('Y-m-d');
                    $updatenya = HeaderDistribusi::updateOrCreate(['nodistribusi' => $head['nodistribusi']], $head);
                }
            }

            $get = CloudHelper::post_readNotif($request->id);
            if (!$get) $unread = null;
            else $unread = json_decode($get->getBody(), true);
            DB::commit();
            return new JsonResponse([
                'message' => $msg,
                'data' => $data,
                'head' => $head ?? null,
                'det' => $det ?? null,
                'prod' => $prod ?? null,
                'unread' => $unread ?? null,
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
    public function unread(Request $request)
    {
        $data = Info::first();
        $get = CloudHelper::get_unread($data->kodecabang);
        if (!$get) $unread = null;
        else $unread = json_decode($get->getBody(), true);
        return new JsonResponse([
            'unread' => $unread
        ]);
    }
}
