<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $data = Product::paginate();
        return ProductResource::collection($data);
    }
    public function store(Request $request)
    {
    }
    public function destroy(Request $request)
    {
    }
}
