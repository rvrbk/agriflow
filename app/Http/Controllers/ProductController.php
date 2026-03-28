<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request)
    {
        app()->make(ProductService::class)->store($request->all());

        return response()->json([
            $request->all()
        ], 201);
    }
}
