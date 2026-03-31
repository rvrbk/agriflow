<?php

namespace App\Http\Controllers;

use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        app()->make(WarehouseService::class)->store($request->all());

        return response()->json([
            $request->all()
        ], 201);
    }
}
