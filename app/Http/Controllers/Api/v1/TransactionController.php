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
        $data = Transaction::where(['nama' => 'BEBAN'])->with(['beban_transaction.beban', 'kasir', 'supplier'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
    }

    public function withPenerimaan()
    {
        // $data = Transaction::paginate();
        $data = Transaction::where(['nama' => 'PENERIMAAN'])->with(['penerimaan_transaction.penerimaan', 'kasir', 'customer'])->latest()->get();
        // ->paginate(request('per_page'));
        // $data->load('product');
        return TransactionResource::collection($data);
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
}
