<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\BebanTransaction;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        // $data = Transaction::paginate();
        $data = Transaction::orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->limit(5)->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function withDetail()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['reff' => request()->reff])
            ->with(['detail_transaction.product.satuanBesar', 'detail_transaction.product.satuan'])
            ->latest()
            ->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function withBeban()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['nama' => 'PENGELUARAN'])->whereMonth('created_at', '=', date('m'))->with(['beban_transaction.beban', 'kasir', 'supplier'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function withPenerimaan()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['nama' => 'PENDAPATAN'])->whereMonth('created_at', '=', date('m'))->with(['penerimaan_transaction.penerimaan', 'kasir', 'customer', 'dokter'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function history()
    {
        $query = Transaction::query();

        if (request('nama') !== 'all' && request('nama') !== 'draft') {
            $query->where(['nama' => request(['nama'])]);
        } else if (request('nama') === 'draft') {
            $query->where(['status' => 1]);
        } else {

            $query;
        }
        $data = $query->with([
            'kasir',
            'supplier',
            'customer',
            'dokter',
            'penerimaan_transaction.penerimaan',
            'beban_transaction.beban',
            'detail_transaction.product'
        ])
            ->orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->paginate(request('per_page'));
        if ($data) {
            foreach ($data as $key) {
                if ($key['status'] === 3) {
                    $rr = 'R' . $key['reff'];
                    $ret = Transaction::where('reff', $rr)->with('detail_transaction')->first();
                    if ($ret) {
                        $key['retur'] = $ret->detail_transaction;
                        $retur = collect($ret->detail_transaction);
                        foreach ($key['detail_transaction'] as $det) {
                            $temp = $retur->where('product_id', $det['product_id'])->first();
                            if ($temp) {
                                $jumlah = $det['qty'] - $temp->qty;
                                $sub = $det['sub_total'] - $temp->sub_total;
                                $tot = $key['total'] - $temp->sub_total;
                                $det['qty'] = $jumlah;
                                $det['sub_total'] = $sub;
                                $key['total'] = $tot;
                                $kem = $key['kembali'];
                                $key['kembali'] = $key['bayar'] - $tot - $kem;
                            }
                        }
                    }
                }
            }
        }
        return TransactionResource::collection($data);
        // return new JsonResponse($data);
    }

    public function pengeluaran()
    {
        $data = Transaction::where('nama', 'PENGELUARAN')
            ->where('perusahaan_id', null)
            ->whereMonth('tanggal', date('m'))
            // ->whereBetween('tanggal', [date('Y-m-01 00:00:00'), date('Y-m-31 23:59:59')])
            ->with('beban_transaction.beban', 'kasir')
            ->latest('tanggal')
            ->get();
        return TransactionResource::collection($data);
    }
    public function penerimaan()
    {
        $data = Transaction::where('nama', 'PENDAPATAN')
            // ->where('customer_id', null)
            ->where('dokter_id', null)
            ->whereMonth('tanggal', date('m'))
            ->with('penerimaan_transaction.penerimaan', 'kasir')
            ->latest('tanggal')
            ->get();
        return TransactionResource::collection($data);
    }

    public function getExpired()
    {
        $data = Transaction::where(['nama' => 'PEMBELIAN'])->with(['detail_transaction'])->get();
        $data2 = [];

        foreach ($data as &$value) {
            foreach ($value->detail_transaction as &$key) {
                array_push($data2, $key->expired);
            }
        }
        $today = date('Y-m-d');
        $expired = array_unique($data2);
        $willExpire = [];
        $alreadyExpire = [];
        foreach ($expired as &$key) {
            if ($key > $today) {

                array_push($willExpire, $key);
            } else {

                array_push($alreadyExpire, $key);
            }
        }

        return response()->json([
            'today' => $today,
            'will_expire' => $willExpire,
            'already_expire' => $alreadyExpire,
            'expired' => $expired,
        ]);
    }

    public function periode($query, $date, $hari, $bulan, $to, $from)
    {
        if ($date === 'hari') {
            if (request()->has('hari') && $hari !== null) {
                $query->whereDay('tanggal', '=', $hari);
            } else {
                $query->whereDay('tanggal', '=', date('d'));
            }
        } else if ($date === 'bulan') {
            if (request()->has('bulan') && $bulan !== null) {
                $query->whereMonth('tanggal', '=', $bulan);
            } else {
                $query->whereMonth('tanggal', '=', date('m'));
            }
        } else if ($date === 'spesifik') {
            $query->whereDate('tanggal', '=', $from);
        } else {
            $query->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }
    }

    public function getByDate()
    {
        $query = Transaction::query();
        // ->selectRaw('product_id, harga, sum(qty) as jml');
        // $query->whereHas('transaction', function ($gg) {
        //     $gg->where(['nama' => request('nama'), 'status' => 2]);

        // });
        $query->where('nama', '=', request('nama'));
        $query->where('status', '>=', 2);
        $this->periode($query, request('date'), request('hari'), request('bulan'), request('to'), request('from'),);



        // $data = $query->groupBy('product_id', 'harga')
        //     ->with(['product'])
        //     ->get();
        // return new JsonResponse($data);
        $data = $query->with(['detail_transaction', 'penerimaan_transaction', 'beban_transaction', 'dokter', 'customer', 'supplier'])
            ->latest()->paginate(request('per_page'));

        return TransactionResource::collection($data);
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
    public function gantiStatus(Request $request)
    {

        $validatedData = Validator::make($request->all(), [
            'reff' => 'required',
        ]);
        if ($validatedData->fails()) {
            return response()->json($validatedData->errors(), 422);
        }
        $data = Transaction::where('reff', $request->reff)->first();
        $data->update([
            'status' => $request->status
        ]);
        return new JsonResponse(['message' => 'Status sudah diganti']);
    }

    public function store(Request $request)
    {
        // return new JsonResponse($request->all(), 410);
        $validatedData = Validator::make($request->all(), [
            'reff' => 'required|min:5',
        ]);

        if ($validatedData->fails()) {
            return response()->json($validatedData->errors(), 422);
        }
        // if ($request->nama === 'PENJUALAN' && $request->status === 1) {
        //     $data = LaporanController::singleStok($request->product_id, $request->reff);
        //     if ($data) {
        //         if ($data->stokSekarang <= 0) {
        //             return new JsonResponse(['message' => 'Data Stok ' . $data->nama . ' ' . $data->stokSekarang . ' tidak mencukupi untuk melakukan transaksi', 'produk' => $data], 410);
        //         }
        //     }
        // }
        $simpan = '';
        $simpan2 = '';
        $array2 = '';
        $harga_di_update = '';
        $secondArray = $request->all();
        $secondArray['tanggal'] = $request->tanggal && $request->nama === 'PEMBELIAN' ? $request->tanggal : date('Y-m-d H:i:s');
        $count = Transaction::where('nama', $request->nama)
            ->whereBetween('tanggal', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->where('status', '>=', 2)
            ->count();
        $pre = explode('-', $request->reff);
        $secondArray['nota'] = $request->status === 2 ? $this->nomoring($count) : null;
        unset($secondArray['reff']);
        try {
            $data = '';

            DB::beginTransaction();

            // if (!$request->has('id')) {

            $validatedData = Validator::make($request->all(), [
                'reff' => 'required',
            ]);
            if ($validatedData->fails()) {
                return response()->json($validatedData->errors(), 422);
            }

            $array2 = $secondArray;
            // return response()->json(['message' => 'success', 'data' => $array2, 'request' => $request->all()], 201);

            $data = Transaction::updateOrCreate(['reff' => $request->reff,], $secondArray);

            $simpan2 = $data;

            if ($request->nama === 'PENGELUARAN' && $request->has('beban_id') && $request->sub_total !== '') {

                $data->beban_transaction()->updateOrCreate([
                    'beban_id' => $request->beban_id
                ], [
                    'sub_total' => $request->sub_total,
                    'keterangan' => $request->keterangan

                ]);
            } else if (
                $request->nama === 'PENDAPATAN' && $request->has('penerimaan_id') && $request->sub_total !== ''
            ) {

                $data->penerimaan_transaction()->updateOrCreate([
                    'penerimaan_id' => $request->penerimaan_id
                ], [
                    'sub_total' => $request->sub_total,
                    'keterangan' => $request->keterangan

                ]);
                $simpan = $data;
            } else if ($request->has('product_id') && $request->nama === 'FORM PENYESUAIAN' && $request->qty < 0) {
                $diskon = $request->has('diskon') && $request->diskon !== null ? $request->diskon : 0;
                $harga = $request->has('harga') && $request->harga !== null ? $request->harga : 0;
                $sub_total = $request->has('sub_total') && $request->sub_total !== null ? $request->sub_total : 0;
                $expired = $request->has('expired') && $request->expired !== null ? $request->expired : null;

                $data->detail_transaction()->updateOrCreate([
                    'product_id' => $request->product_id,
                ], [
                    'harga' => $harga,
                    'qty' => $request->qty,
                    'expired' => $expired,
                    'diskon' => $diskon,
                    'sub_total' => $sub_total
                ]);

                // update harga_beli di produk dan harga jual juga
                // if ($request->update_harga) {
                //     $harga_di_update = 'Harga Di Update';
                //     $produk = Product::find($request->product_id);
                //     $selisih = $request->harga - $produk->harga_beli;
                //     // $selisi = $request->harga - $produk->harga_beli;
                //     // $selisih = $selisi <= 0 ? 0 : $selisi;

                //     $produk->update([
                //         'harga_jual_umum' => $produk->harga_jual_umum + $selisih,
                //         'harga_jual_resep' => $produk->harga_jual_resep + $selisih,
                //         'harga_jual_cust' => $produk->harga_jual_cust + $selisih,
                //         // 'harga_beli' => $request->harga
                //         'harga_beli' => $request->harga_beli + $selisih
                //     ]);
                // }
                // if ($request->has('rak_id')) {
                //     $produk = Product::find($request->product_id);
                //     $produk->update(['rak_id' => $request->rak_id]);
                // }
            } else if ($request->has('product_id') && $request->qty > 0) {

                $diskon = $request->has('diskon') && $request->diskon !== null ? $request->diskon : 0;
                $harga = $request->has('harga') && $request->harga !== null ? $request->harga : 0;
                $sub_total = $request->has('sub_total') && $request->sub_total !== null && $request->sub_total > 0 ? $request->sub_total : ((int) $request->qty * (int) $harga);


                $data->detail_transaction()->updateOrCreate(
                    [
                        'product_id' => $request->product_id,
                    ],
                    [
                        'harga_beli' => $request->harga_beli ?? 0,
                        'harga' => $harga,
                        'qty' => $request->qty,
                        'racikan' => $request->racikan,
                        'nilai_r' => $request->nilai_r ?? 0,
                        // 'diskon' => $diskon,
                        'sub_total' => $sub_total
                    ]
                );

                // update harga_beli di produk dan harga jual juga
                if ($request->update_harga) {
                    $produk = Product::find($request->product_id);
                    $harga_di_update = 'Harga Di Update';
                    $disperitem = 0;
                    if ($request->diskon > 0) {
                        $disperitem = $request->harga * ($request->diskon / 100);
                    }

                    // $selisih = $request->harga - $produk->harga_beli;
                    // $selisi = ($request->harga - $produk->harga_beli) - $disperitem;
                    // $selisih = $selisi <= 0 ? 0 : $selisi;
                    $hargaBeli = $request->harga - $disperitem;
                    $sepuluh = $hargaBeli * (10 / 100);
                    $duapuluh = $hargaBeli * (20 / 100);

                    $produk->update([
                        'harga_jual_umum' => $hargaBeli + ($produk->hv === '1' ? $sepuluh : $duapuluh + 1000),
                        'harga_jual_resep' => $hargaBeli + $sepuluh + 1000,
                        'harga_jual_cust' => $hargaBeli,
                        'harga_jual_prem' => $hargaBeli,
                        'harga_jual_rac' => $hargaBeli + $duapuluh,
                        'harga_beli' => $request->harga - $disperitem
                        // 'harga_beli' => $produk->harga_beli + $selisih
                    ]);
                }
                // if ($request->nama === 'PENJUALAN' && $request->status === 2) {
                //     if ($request->jenis === 'tunai') {
                // // cek subtotal
                $det = DetailTransaction::where('transaction_id', $data->id)->get();
                $detCol = collect($det)->sum('sub_total');
                $total = $data->total;
                if ($request->nama === 'PENJUALAN') {
                    if ($total !== $detCol) {
                        $data->total = $detCol;
                        $data->totalSemua = $detCol;
                        // $data->save();
                        if ($request->jenis === 'tunai') {

                            if ($data->bayar > 0) {
                                $data->kembali = $data->bayar - $detCol;
                            }
                        }
                        $data->save();
                    }


                    // }
                    // }
                }

                /*
            * new
            * koding ongkir / PPN  disini
            * PPN  dalam %
            */
                $tr = null;
                $deta = null;
                $produ = [];
                if ($request->nama === 'PEMBELIAN' && $request->status === 2) {
                    if ($request->ongkir > 0 || $request->potongan > 0) {
                        $transaksi = Transaction::where('reff', $request->reff)->first();
                        $tr = $transaksi;
                        $det = DetailTransaction::where('transaction_id', $transaksi->id)->get();
                        $deta = $det;
                        foreach ($det as $key) {
                            $prod = Product::find($key['product_id']);
                            $harga = 0;
                            $hargaPpn = 0;

                            if ($request->potongan > 0) {
                                $discPerItem = $prod->harga_beli  * ($request->potongan / 100);
                                $harga =  $prod->harga_beli - $discPerItem;
                                array_push($produ, $harga);
                            }
                            if ($request->ongkir > 0) {
                                if ($harga > 0) {
                                    $ppnPerItem = $harga  * ($request->ongkir / 100);
                                    $hargaPpn = $harga + $ppnPerItem;
                                    array_push($produ, $hargaPpn);
                                } else {
                                    $ppnPerItem = $prod->harga_beli  * ($request->ongkir / 100);
                                    $hargaPpn = $prod->harga_beli + $ppnPerItem;
                                    array_push($produ, $hargaPpn);
                                }
                            }

                            $harg = ceil($hargaPpn);
                            $selisi = ceil($harg - $prod->harga_beli);
                            $selisih = $selisi <= 0 ? 0 : $selisi;
                            $prod->update([
                                'harga_jual_umum' => $prod->harga_jual_umum + $selisih,
                                'harga_jual_resep' => $prod->harga_jual_resep + $selisih,
                                'harga_jual_cust' => $prod->harga_jual_cust + $selisih,
                                'harga_jual_prem' => $prod->harga_jual_prem + $selisih,
                                'harga_beli' => $harg
                                // 'harga_beli' => $produk->harga_beli + $selisih
                            ]);
                        }
                    }
                }

                /*
            * koding ongkir / PPN  disini
            * PPN  dalam %
            */
                // if ($request->ongkir > 0 && $request->nama === 'PEMBELIAN' && $request->status === 2) {
                //     $transaksi = Transaction::where('reff', $request->reff)->with('detail_transaction')->first();
                //     $subDetail = collect($transaksi->detail_transaction)->map(function ($item, $key) {
                //         return $item->qty * $item->harga;
                //     })->sum();
                //     $selisihtotal = $request->totalSemua - $subDetail;
                //     if ($selisihtotal > 0) {
                //         $harga_di_update = 'Harga Di Update';
                //         foreach ($transaksi->detail_transaction as $detail) {
                //             $produk = Product::find($detail->product_id);
                //             // kalo ada ongkir berarti harga beli tidak di ubah diawal
                //             // jadi harga baru langsung di naikkan dari harga beli

                //             // selisih harga = harga / totalHaga * selisihTotal
                //             // $selisihHarga = round($detail->harga / $subDetail * $selisihtotal, 2);
                //             $selisihHarga = ceil($detail->harga / $subDetail * $selisihtotal);
                //             $hargaBaru = $detail->harga + $selisihHarga;
                //             $selishiBaru = $hargaBaru - $produk->harga_beli;
                //             $gap = $selishiBaru <= 0 ? 0 : $selishiBaru;
                //             $produk->update([
                //                 'harga_jual_umum' => $produk->harga_jual_umum + $gap,
                //                 'harga_jual_resep' => $produk->harga_jual_resep + $gap,
                //                 'harga_jual_cust' => $produk->harga_jual_cust + $gap,
                //                 'harga_beli' => $produk->harga_beli + $gap
                //             ]);
                //         }
                //     }

                //     // foreach ($transaksi->detail_transaction as $detail) {
                //     //     $produk = Product::find($detail->product_id);
                //     //     // kalo ada ongkir berarti harga beli tidak di ubah diawal
                //     //     // jadi harga baru langsung di naikkan dari harga beli
                //     //     // cara hitung => hitung diskon per item dulu, kemudian hitung diskon global, setelah itu di hitung ppn nya
                //     //     $ppn = $produk->harga_beli * $transaksi->ongkir / 100;
                //     //     $jadiPPN = $ppn <= 0 ? 0 : $ppn;
                //     //     // $produk->ppn = $ppn;

                //     //     // $detail->product = $produk;
                //     // }
                //     // return new JsonResponse($transaksi, 500);
                // }

                if ($request->has('pbreff')) {
                    HutangController::statusPembelian($request);
                }
            }
            if (!$request->has('product_id') && $request->nama === 'PENJUALAN') {

                $det = DetailTransaction::where('transaction_id', $data->id)->get();
                $hitungTot = collect($det)->sum('sub_total');
                $bayar = (float)$request->bayar;
                $totalnya = (float)$hitungTot;
                if ($bayar < $totalnya) {
                    return new JsonResponse([
                        'message' => 'Periksa kembali jumlah bayar',
                        'bayar' => $request->bayar,
                        'detCol' => $hitungTot,
                    ], 410);
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'success',
                'update_harga' => $harga_di_update,
                'data' => $data,
                'det' => $detCol ?? null,
                'total' => $total ?? null
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $th,
                'tr' => $tr,
                'produ' => $produ,
                'deta' => $deta,
                'simpan' => $simpan,
                'simpan 2' => $simpan2,
                'second array' => $array2,
                'request' => $request->all()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Transaction::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Transaction {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
    public function destroyDraft()
    {
        // $auth = auth()->user()->id;


        // $data = Transaction::find(21);
        $data = [];
        if (request('nama') === 'all' || request('nama') === 'draft' || request('nama') === '') {

            $data = Transaction::where(['status' => 1])->get();
        } else {

            $data = Transaction::where(['nama' => request('nama'), 'status' => 1])->get();
        }
        // return response()->json(['data' => $data]);
        $del = [];
        if (count($data) >= 1) {

            foreach ($data as &$value) {

                $del = $value->delete();
            }

            if (!$del) {
                return response()->json([
                    'message' => 'Error on Delete'
                ], 500);
            }

            // $user->log("Menghapus Data Transaction {$data->nama}");
            return response()->json([
                'message' => 'Data sukses terhapus'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Tidak ada draft yang perlu di hapus'
            ], 200);
        }
    }
    public function deleteBebanTransaction(Request $request)
    {
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = BebanTransaction::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Transaction {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
