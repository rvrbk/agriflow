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
            ->leftJoin('corporations', 'warehouses.corporation_id', '=', 'corporations.id')
            ->orderBy('warehouses.name')
            ->get([
                'warehouses.id',
                'warehouses.uuid',
                'warehouses.name',
                'warehouses.address',
                'warehouses.city',
                'warehouses.state',
                'warehouses.country',
                'warehouses.location',
                'corporations.uuid as corporation_uuid',
                'corporations.name as corporation_name',
            ])
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

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function delete(string $uuid): JsonResponse
    {
        $result = app()->make(WarehouseService::class)->deleteByUuid($uuid);

        if ($result === WarehouseService::DELETE_NOT_FOUND) {
            return response()->json([
                'message' => 'Warehouse not found.',
            ], 404);
        }

        if ($result === WarehouseService::DELETE_LINKED_TO_INVENTORY) {
            return response()->json([
                'message' => 'Warehouse is linked to inventory records and cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'message' => 'Warehouse deleted.',
        ]);
    }
}
