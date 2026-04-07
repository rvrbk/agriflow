<?php

namespace App\Http\Controllers;


use App\Models\Inventory;
use App\Services\HarvestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HarvestController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $harvests = Inventory::query()
            ->join('batches', 'inventories.batch_id', '=', 'batches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('corporations', 'batches.corporation_id', '=', 'corporations.id')
            ->orderBy('batches.created_at', 'desc')
            ->select([
                'batches.uuid as batch_uuid',
                'batches.harvested_on',
                'batches.expires_on',
                'batches.quality',
                'batches.qr_code',
                'batches.qr_payload',
                'products.uuid as product_uuid',
                'products.name as product_name_translations',
                'warehouses.uuid as warehouse_uuid',
                'warehouses.name as warehouse_name',
                'corporations.uuid as corporation_uuid',
                'corporations.name as corporation_name',
                'inventories.quantity',
                'inventories.location',
                'inventories.available_on',
            ])
            ->get()
            ->map(function ($harvest): array {
                $translations = json_decode($harvest->product_name_translations ?? '{}', true);

                return [
                    'batch_uuid' => $harvest->batch_uuid,
                    'product_uuid' => $harvest->product_uuid,
                    'product_name' => is_array($translations) ? ($translations['en'] ?? '') : '',
                    'warehouse_uuid' => $harvest->warehouse_uuid,
                    'warehouse_name' => $harvest->warehouse_name,
                    'corporation_uuid' => $harvest->corporation_uuid,
                    'corporation_name' => $harvest->corporation_name,
                    'quantity' => (float) $harvest->quantity,
                    'location' => $harvest->location,
                    'available_on' => $harvest->available_on,
                    'harvested_on' => $harvest->harvested_on,
                    'expires_on' => $harvest->expires_on,
                    'quality' => $harvest->quality,
                    'qr_code' => $harvest->qr_code,
                    'qr_payload' => $harvest->qr_payload,
                    'qr_url' => url('/harvest/' . $harvest->batch_uuid),
                ];
            })
            ->values();

        return response()->json($harvests);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        app()->make(HarvestService::class)->store($request->all());

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
        $deleted = app()->make(HarvestService::class)->deleteByBatchUuid($uuid);

        if (!$deleted) {
            return response()->json([
                'message' => 'Harvest not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Harvest deleted.',
        ]);
    }

    /**
     * @param string $batchUuid
     * @return View
     */
    public function show(string $batchUuid): View
    {
        $harvest = Inventory::query()
            ->join('batches', 'inventories.batch_id', '=', 'batches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('corporations', 'batches.corporation_id', '=', 'corporations.id')
            ->where('batches.uuid', $batchUuid)
            ->select([
                'batches.uuid as batch_uuid',
                'batches.harvested_on',
                'batches.expires_on',
                'batches.quality',
                'batches.qr_code',
                'batches.qr_payload',
                'products.uuid as product_uuid',
                'products.name as product_name_translations',
                'warehouses.uuid as warehouse_uuid',
                'warehouses.name as warehouse_name',
                'corporations.uuid as corporation_uuid',
                'corporations.name as corporation_name',
                'inventories.quantity',
                'inventories.location',
                'inventories.available_on',
            ])
            ->firstOrFail();

        $translations = json_decode($harvest->product_name_translations ?? '{}', true);

        return view('harvest.show', [
            'harvest' => [
                'batch_uuid' => $harvest->batch_uuid,
                'product_uuid' => $harvest->product_uuid,
                'product_name' => is_array($translations) ? ($translations['en'] ?? '') : '',
                'warehouse_uuid' => $harvest->warehouse_uuid,
                'warehouse_name' => $harvest->warehouse_name,
                'corporation_uuid' => $harvest->corporation_uuid,
                'corporation_name' => $harvest->corporation_name,
                'quantity' => (float) $harvest->quantity,
                'location' => $harvest->location,
                'available_on' => $harvest->available_on,
                'harvested_on' => $harvest->harvested_on,
                'expires_on' => $harvest->expires_on,
                'quality' => $harvest->quality,
                'qr_code' => $harvest->qr_code,
                'qr_payload' => $harvest->qr_payload,
            ],
        ]);
    }
}
