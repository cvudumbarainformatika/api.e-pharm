<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\CloudHelper;
use App\Http\Controllers\Controller;
use App\Models\Beban;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\DistribusiAntarToko;
use App\Models\Dokter;
use App\Models\HeaderDistribusi;
use App\Models\Kategori;
use App\Models\Merk;
use App\Models\Perusahaan;
use App\Models\Product;
use App\Models\Rak;
use App\Models\Satuan;
use App\Models\SatuanBesar;
use App\Models\Setting\Info;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function read(Request $request)
    {
        if ($request->is_read != 0) {
            if ($request->model === 'HeaderDistribusi') {
                return new JsonResponse([
                    'message' => 'notifikasi sudah dibaca, silahkan cek History distribusi'
                ], 410);
            } else {
                return new JsonResponse([
                    'message' => 'notifikasi sudah dibaca'
                ], 410);
            }
        }
        $me = Info::first();
        if ($request->receiver != $me->kodecabang) {
            return new JsonResponse([
                'message' => 'Penerima Notifikasi bukan Cabang ini'
            ], 410);
        }
        try {
            DB::beginTransaction();
            $content = $request->content;
            $msg = 'Notifikasi sudah dibaca dan dilaksanan';
            if ($request->model === 'HeaderDistribusi') {
                // cek status
                $head = $content;
                $stat = HeaderDistribusi::where('nodistribusi', $head['nodistribusi'])->first();
                $myStat = $stat->status ?? 0;
                // jaga-jaga ada kirimian 2 kali, jadi cek statusnya dulu... kalo sama ya ga usah di apa2ian..
                if ($myStat < $head['status']) {
                    $det = $content['details'];
                    unset($head['details']);
                    if (count($det) > 0) {
                        foreach ($det as $key) {
                            $prod = Product::select('id', 'kode_produk', 'nama')->where('kode_produk', $key['kode_produk'])->first();
                            if (!$prod) {
                                return new JsonResponse([
                                    'message' => 'Produk ' . $key['kode_produk'] . ' (Item Obat) tidak ada dicabang ini'
                                ], 410);
                            }
                            $key['product_id'] = $prod->id;
                            // return new JsonResponse($key['nodistribusi']);

                            $dist = DistribusiAntarToko::updateOrCreate([
                                'nodistribusi' => $key['nodistribusi'],
                                'product_id' => $key['product_id'],
                            ], $key);
                        }
                    }
                    if ($request->type == 'kiriman distribusi') {
                        $head['tgl_terima'] = date('Y-m-d');
                        $msg = 'Notifikasi sudah dibaca dan dilaksanan, Silahkan Cek history distribusi';
                    } else {
                        $msg = 'Notifikasi sudah dibaca dan dilaksanan, Silahkan Cek history distribusi untuk proses distribusi';
                    }
                    $updatenya = HeaderDistribusi::updateOrCreate(['nodistribusi' => $head['nodistribusi']], $head);
                }
            } else if ($request->type === 'update master') {
                $model = [
                    [
                        'name' => Beban::class,
                        'sring' => 'Beban',
                        'kode' => 'kode_beban',
                    ],
                    [
                        'name' => Cabang::class,
                        'sring' => 'Cabang',
                        'kode' => 'kodecabang',
                    ],
                    [
                        'name' => Customer::class,
                        'sring' => 'Customer',
                        'kode' => 'kode_customer',
                    ],
                    [
                        'name' => Dokter::class,
                        'sring' => 'Dokter',
                        'kode' => 'kode_dokter',
                    ],
                    [
                        'name' => Kategori::class,
                        'sring' => 'Kategori',
                        'kode' => 'kode_kategory',
                    ],
                    [
                        'name' => Merk::class,
                        'sring' => 'Merk',
                        'kode' => 'kode_merk',
                    ],
                    [
                        'name' => Product::class,
                        'sring' => 'Product',
                        'kode' => 'kode_produk',
                    ],
                    [
                        'name' => Rak::class,
                        'sring' => 'Rak',
                        'kode' => 'kode_rak',
                    ],
                    [
                        'name' => Satuan::class,
                        'sring' => 'Satuan',
                        'kode' => 'kode_satuan',
                    ],
                    [
                        'name' => SatuanBesar::class,
                        'sring' => 'SatuanBesar',
                        'kode' => 'kode_satuan',
                    ],
                    [
                        'name' => Supplier::class,
                        'sring' => 'Supplier',
                        'kode' => 'kode_supplier',
                    ],
                    [
                        'name' => Perusahaan::class,
                        'sring' => 'Perusahaan',
                        'kode' => 'kode',
                    ],
                ];
                $str = $request->model;
                $keys = array_column($model, 'sring');
                $ind = array_search($str, $keys);
                if ($str === 'Product') {
                    unset($content['stok_awal']);
                }
                $kode = $model[$ind]['kode'];
                $data = $model[$ind]['name']::updateOrCreate(
                    [$kode => $content[$kode]],
                    $content,
                );
                $mod = $model[$ind]['sring'] === 'Product' ? 'Produk' : $model[$ind]['sring'];
                $msg = 'Notifikasi sudah dibaca dan ' . $request->type . ' ' . $mod . ' sudah dilaksanakan';
            } else if ($request->type === 'delete master') {
                $model = [
                    [
                        'name' => Beban::class,
                        'sring' => 'Beban',
                        'kode' => 'kode_beban',
                    ],
                    [
                        'name' => Cabang::class,
                        'sring' => 'Cabang',
                        'kode' => 'kodecabang',
                    ],
                    [
                        'name' => Customer::class,
                        'sring' => 'Customer',
                        'kode' => 'kode_customer',
                    ],
                    [
                        'name' => Dokter::class,
                        'sring' => 'Dokter',
                        'kode' => 'kode_dokter',
                    ],
                    [
                        'name' => Kategori::class,
                        'sring' => 'Kategori',
                        'kode' => 'kode_kategory',
                    ],
                    [
                        'name' => Merk::class,
                        'sring' => 'Merk',
                        'kode' => 'kode_merk',
                    ],
                    [
                        'name' => Product::class,
                        'sring' => 'Product',
                        'kode' => 'kode_produk',
                    ],
                    [
                        'name' => Rak::class,
                        'sring' => 'Rak',
                        'kode' => 'kode_rak',
                    ],
                    [
                        'name' => Satuan::class,
                        'sring' => 'Satuan',
                        'kode' => 'kode_satuan',
                    ],
                    [
                        'name' => SatuanBesar::class,
                        'sring' => 'SatuanBesar',
                        'kode' => 'kode_satuan',
                    ],
                    [
                        'name' => Supplier::class,
                        'sring' => 'Supplier',
                        'kode' => 'kode_supplier',
                    ],
                    [
                        'name' => Perusahaan::class,
                        'sring' => 'Perusahaan',
                        'kode' => 'kode',
                    ],
                ];
                $str = $request->model;
                $keys = array_column($model, 'sring');
                $ind = array_search($str, $keys);
                $kode = $model[$ind]['kode'];
                $data = $model[$ind]['name']::where($kode, $content[$kode])->first();
                if (!$data) {
                    return new JsonResponse([
                        'message' => 'master ' . $model[$ind]['sring'] . ' dengan ' . $kode . ' ' . $content[$kode] . ' tidak ada dicabang ini'
                    ], 410);
                }
                $data->delete();
                $mod = $model[$ind]['sring'] === 'Product' ? 'Produk' : $model[$ind]['sring'];
                $msg = 'Notifikasi sudah dibaca dan ' . $request->type . ' ' . $mod . ' sudah dilaksanakan';
            }

            $get = CloudHelper::post_readNotif($request->id);
            if (!$get) $unread = null;
            else $unread = json_decode($get->getBody(), true);
            DB::commit();
            return new JsonResponse([
                'message' => $msg,
                'data' => $content ?? null,
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
    public function report(Request $request)
    {
        $me = Info::first();
        // $header = (object) [
        //     'periode' => 'bulan', // bulan atau range,, kosong berarti hari ini tergantung from dan to
        //     'from' => '2024-07-01',
        //     'to' => '2024-07-30',
        // ];
        $header = (object)$request->content;
        // return new JsonResponse([
        //     'message' => 'Laporan tidak dikirim ke cloud',
        //     'balasan' => $header,
        // ], 410);
        $data = CloudReportController::report($header);
        $msg = [
            'norequest' => $request->model,
            'tgl' => $request->tgl,
            'header' => $header,
            'laporan' => $data,
            'kodecabang' => $me->kodecabang
        ];
        $post = CloudHelper::post_sendReport($msg);
        if (!$post) $anu = null;
        else $anu = json_decode($post->getBody(), true);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Laporan gagal dikirim ke cloud, data tidak ditemukan',
                'data' => $data
            ], 410);
        }
        return new JsonResponse([
            'message' => 'Laporan dikirim ke cloud',
            'balasan' => $anu,
        ]);
    }
}
