<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting\Info;
use App\Models\Setting\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // bagian info
    public function getInfo()
    {
        $data = Info::get();
        return new JsonResponse($data);
    }

    public function storeInfo(Request $request)
    {
        return new JsonResponse((array) $request->all());
        try {
            DB::beginTransaction();
            Info::updatOrCreate(
                ['id' => $request->id],
                $request->all()
            );

            DB::commit();
            return new JsonResponse(['message' => 'sukses'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'error', 'error' => $e], 500);
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
            if ($request->has('id')) {
                $data = Menu::find($request->id);
                $data->update($request->all());
            } else {
                Menu::create($request->all());
            }

            DB::commit();
            return new JsonResponse(['message' => 'sukses'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'error', 'error' => $e], 500);
        }
    }
    public static function simpanMenu($request)
    {
        // return (array) $request;

        $data = Menu::updatOrCreate(
            ['id' => $request->id],
            $request
        );
        if (!$data) {
            return new JsonResponse(['message' => 'gagal'], 500);
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
