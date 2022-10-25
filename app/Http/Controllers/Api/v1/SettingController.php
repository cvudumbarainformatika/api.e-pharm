<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting\Info;
use App\Models\Setting\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    // bagian info
    public function getInfo()
    {
        $data = Info::get();
        return new JsonResponse($data, 200);
    }
    public function publicInfo()
    {
        $data = Info::first();
        return new JsonResponse($data, 200);
    }

    public function storeInfo(Request $request)
    {
        // return new JsonResponse((array) $request->all());
        $data = null;
        $first = $request->nama;
        $second = $request->all();
        unset($second['nama']);
        try {
            DB::beginTransaction();
            $valid = Validator::make($request->all(), [
                'nama' => 'required'
            ]);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            $data = Info::updateOrCreate(['nama' => $first], $second);

            DB::commit();
            return new JsonResponse(['message' => 'sukses'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'error',
                'error' => $e,
                'first' => $first,
                'second' => $second,
                'data' => $data,
            ], 500);
        }
    }
    // bagian menu
    public function getMenu()
    {
        $data = Menu::get();
        return new JsonResponse($data);
    }

    public function storeMenu(Request $request)
    {
        // return new JsonResponse((array) $request->all());
        try {
            DB::beginTransaction();
            $valid = Validator::make($request->all(), [
                'nama' => 'required'
            ]);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            Menu::updateOrCreate(
                ['nama' => $request->nama],
                $request->all()
            );
            // if ($request->has('id')) {
            //     $data = Menu::find($request->id);
            //     $data->update($request->all());
            // } else {
            //     Menu::create($request->all());
            // }

            DB::commit();
            return new JsonResponse(['message' => 'sukses'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'error', 'error' => $e], 500);
        }
    }
    public static function simpanMenu($request)
    {
        // return (array) $request;

        $data = Info::updatOrCreate(
            ['nama' => $request['nama']],
            $request
        );
        if (!$data) {
            return new JsonResponse(['message' => 'gagal'], 204);
        }
        return new JsonResponse(['message' => 'good'], 201);

        try {
            DB::beginTransaction();

            DB::commit();
            return new JsonResponse(['message' => 'sukses', 'data' => $data], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'error', 'error' => $e, 'data' => $data], 500);
        }
    }
}
