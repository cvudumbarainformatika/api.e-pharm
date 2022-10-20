<?php

namespace App\Http\Controllers;

use App\Http\Resources\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unprocessable Entity'], 422);
        }
        return $this->createNewToken($token);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'role' => 'required',
            'password' => 'required|string|confirmed|min:6'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }
        $level = 4;
        switch ($request->role) {
            case 'kasir':
                $level = 5;
                break;
            case 'gudang':
                $level = 5;
                break;
            case 'admin':
                $level = 4;
                break;
            case 'manager':
                $level = 3;
                break;
            case 'owner':
                $level = 2;
                break;

            default:
                $level = 5;
                break;
        }
        $user = User::create(array_merge($validator->validated(), ['password' => bcrypt($request->password), 'level' => $level]));

        if (!$user) {
            return new JsonResponse(['message' => 'registrasi gagal'], 204);
        }
        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user,
            // 'valid' => array_merge($validator->validated(), ['password' => bcrypt($request->password)])
        ], 201);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'role' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }
        $level = 4;
        switch ($request->role) {
            case 'kasir':
                $level = 5;
                break;
            case 'gudang':
                $level = 5;
                break;
            case 'admin':
                $level = 4;
                break;
            case 'manager':
                $level = 3;
                break;
            case 'owner':
                $level = 2;
                break;

            default:
                $level = 5;
                break;
        }
        $user = User::find($request->id);
        $user->update(array_merge($validator->validated(), ['level' => $level]));

        if (!$user) {
            return new JsonResponse(['message' => 'update gagal'], 204);
        }
        return response()->json([
            'message' => 'User Berhasil di update',
            'user' => $user,
            // 'valid' => array_merge($validator->validated(), ['password' => bcrypt($request->password)])
        ], 200);
    }
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->user());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function userAll()
    {
        $data = User::filter(request(['q']))->latest()->paginate(request('per_page'));
        return UserResource::collection($data);
    }

    public function userKasir()
    {
        $data = User::where(['role' => 'kasir'])->get();
        return UserResource::collection($data);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()
        ]);
    }
}
