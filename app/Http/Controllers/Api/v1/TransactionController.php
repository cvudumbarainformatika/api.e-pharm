<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        // $data = Transaction::paginate();
        $data = Transaction::orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }
    public function withDetail()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['reff' => request()->reff])->with(['detail_transaction', 'detail_transaction.product'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function withBeban()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['nama' => 'BEBAN'])->whereMonth('created_at', '=', date('m'))->with(['beban_transaction.beban', 'kasir', 'supplier'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function withPenerimaan()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['nama' => 'PENERIMAAN'])->whereMonth('created_at', '=', date('m'))->with(['penerimaan_transaction.penerimaan', 'kasir', 'customer', 'dokter'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function history()
    {
        if (request('nama') !== 'all' && request('nama') !== 'draft') {

            $data = Transaction::where(['nama' => request(['nama'])])
                ->with([
                    'kasir',
                    'supplier',
                    'customer',
                    'dokter',
                    'penerimaan_transaction.penerimaan',
                    'beban_transaction.beban',
                    'detail_transaction.product'
                ])
                ->orderBy(request()->order_by, request()->sort)
                ->filter(request(['q']))->latest()->paginate(request('per_page'));
            return TransactionResource::collection($data);
        } else if (request('nama') === 'draft') {

            $data = Transaction::where(['status' => 0])
                ->with(['kasir', 'supplier', 'customer', 'dokter', 'penerimaan_transaction', 'beban_transaction'])
                ->orderBy(request()->order_by, request()->sort)
                ->filter(request(['q']))->latest()->paginate(request('per_page'));
            return TransactionResource::collection($data);
        } else {

            $data = Transaction::with([
                'kasir',
                'supplier',
                'customer',
                'dokter',
                'penerimaan_transaction.penerimaan',
                'beban_transaction.beban',
                'detail_transaction.product'
            ])
                ->orderBy(request()->order_by, request()->sort)
                ->filter(request(['q']))->latest()->paginate(request('per_page'));
            return TransactionResource::collection($data);
        }
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

    public function store(Request $request)
    {
        // $data = $request->all();

        // unset($data['reff']);

        // return response()->json(['message' => 'success', 'data' => $data, 'request' => $request->all()], 201);
        // $auth = $request->user();
        $simpan = '';
        $simpan2 = '';
        $array2 = '';
        // $secondArray = '';
        $secondArray = $request->all();
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

            if ($request->nama === 'BEBAN' && $request->has('beban_id')) {

                $data->beban_transaction()->updateOrCreate([
                    'beban_id' => $request->beban_id
                ], [
                    'sub_total' => $request->sub_total,
                    'keterangan' => $request->keterangan

                ]);
            } else if (
                $request->nama === 'PENERIMAAN' && $request->has('penerimaan_id')
            ) {

                $data->penerimaan_transaction()->updateOrCreate([
                    'penerimaan_id' => $request->penerimaan_id
                ], [
                    'sub_total' => $request->sub_total,
                    'keterangan' => $request->keterangan

                ]);
            } else if ($request->has('product_id')) {

                $data->detail_transaction()->updateOrCreate([
                    'product_id' => $request->product_id,
                ], [
                    'harga' => $request->harga,
                    'qty' => $request->qty,
                    'expired' => $request->expired,
                    'sub_total' => $request->sub_total
                ]);

                $simpan = $data;
            }

            DB::commit();
            return response()->json(['message' => 'success', 'data' => $data], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $e,
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

            $data = Transaction::where(['status' => 0])->get();
        } else {

            $data = Transaction::where(['nama' => request('nama'), 'status' => 0])->get();
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
}
