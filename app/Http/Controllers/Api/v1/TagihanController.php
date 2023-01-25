<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagihanController extends Controller
{
    public function piutang()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('jenis', 'piutang')
            ->where('status', '=', 2)
            ->oldest('tempo')
            ->with('customer', 'dokter', 'detail_transaction.product')
            ->get();

        return new JsonResponse($data);
    }
    public function transaksiTerbayar()
    {
        $data = Transaction::where('nama', 'PENJUALAN')
            ->where('jenis', 'piutang')
            ->where('status', '=', 5)
            ->oldest('tanggal')
            ->with('customer', 'dokter', 'detail_transaction.product')
            ->get();
        $terbayar = collect($data)->sum('total');

        return new JsonResponse($terbayar);
    }
    public function tagihanTerbayar()
    {
        $data = Tagihan::where('nama', 'TAGIHAN')
            ->where('status', '=', 3)
            ->latest('tanggal')
            ->with('kasir', 'details.penerimaan', 'details.dokter', 'details.customer')
            ->get();

        return new JsonResponse($data);
    }
    public function tagihan()
    {
        $data = Tagihan::where('nama', 'TAGIHAN')
            ->where('status', '=', 2)
            ->latest('tanggal')
            ->with('kasir', 'details.penerimaan', 'details.dokter', 'details.customer')
            ->get();

        foreach ($data as $tagihan) {
            foreach ($tagihan->details as $detail) {
                $penjualan = Transaction::where('reff', $detail->nota)->first();
                $detail->penjualan = $penjualan;
            }
        }

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $second = $request->all();
        $second['tanggal'] = date('Y-m-d H:i:s');
        try {
            DB::beginTransaction();
            $validatedData = Validator::make($request->all(), [
                'reff' => 'required',
            ]);
            if ($validatedData->fails()) {
                return response()->json($validatedData->errors(), 422);
            }
            $data = Tagihan::UpdateOrCreate(['reff' => $request->reff], $second);
            $data->details()->updateOrCreate(['nota' => $request->nota], $second);
            if ($request->has('pjreff')) {
                $this->tertagih($request);
            }
            DB::commit();
            return new JsonResponse(['message' => 'Success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan, gagal menyimpan'
            ], 500);
        }
    }
    public function dibayar(Request $request)
    {
        $data = Tagihan::where('reff', $request->reff)->first();
        $semua = $request->all();
        $detail = $semua['details'];
        $data->status = 3;
        if (!$data->save()) {
            return new JsonResponse(['message' => 'gagal'], 500);
        }
        foreach ($detail as &$key) {
            $this->terbayar($key);
        }
        return new JsonResponse(['message' => 'success'], 200);
        // return new JsonResponse([
        //     'semua' => $semua,
        //     'detail' => $detail,
        //     'data' => $data,
        // ]);
    }
    public function tertagih($header)
    {
        $data = Transaction::where('reff', $header->pjreff)->first();
        $data->status = 4;
        if (!$data->save()) {
            return new JsonResponse(['message' => 'gagal'], 500);
        }
        return new JsonResponse(['message' => 'success'], 200);
    }
    public function terbayar($header)
    {
        $data = Transaction::where('reff', $header['nota'])->first();
        $data->status = 5;
        if (!$data->save()) {
            return new JsonResponse(['message' => 'gagal'], 500);
        }
        return new JsonResponse(['message' => 'success'], 200);
    }
}
