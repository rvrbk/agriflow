<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get(['uuid', 'name'])
            ->values();

        return response()->json($warehouses);
    }

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
