<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $inventoryRows = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('batches', 'inventories.batch_id', '=', 'batches.id')
            ->leftJoin('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->orderBy('products.name')
            ->orderBy('batches.harvested_on', 'desc')
            ->select([
                'inventories.id',
                'inventories.quantity',
                'inventories.location',
                'inventories.available_on',
                'products.uuid as product_uuid',
                'products.name as product_name_translations',
                'products.unit as product_unit',
                'batches.uuid as batch_uuid',
                'batches.harvested_on',
                'batches.quality',
                'warehouses.name as warehouse_name',
            ])
            ->get()
            ->map(function ($row): array {
                $translations = json_decode($row->product_name_translations ?? '{}', true);

                return [
                    'inventory_id' => $row->id,
                    'product_uuid' => $row->product_uuid,
                    'product_name' => is_array($translations) ? ($translations['en'] ?? '') : '',
                    'product_unit' => $row->product_unit,
                    'batch_uuid' => $row->batch_uuid,
                    'harvested_on' => $row->harvested_on,
                    'quality' => $row->quality,
                    'quantity' => (float) $row->quantity,
                    'location' => $row->location,
                    'available_on' => $row->available_on,
                    'warehouse_name' => $row->warehouse_name,
                ];
            })
            ->values();

        return response()->json($inventoryRows);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_uuid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', 'in:add,subtract'],
        ]);

        $batch = Batch::where('uuid', $validated['batch_uuid'])->first();

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found.',
            ], 404);
        }

        $inventory = Inventory::where('batch_id', $batch->id)->first();

        if (!$inventory) {
            return response()->json([
                'message' => 'Inventory not found.',
            ], 404);
        }

        $amount = (float) $validated['amount'];

        if ($validated['direction'] === 'add') {
            $inventory->quantity = (float) $inventory->quantity + $amount;
        } else {
            if ((float) $inventory->quantity < $amount) {
                return response()->json([
                    'message' => 'Insufficient inventory quantity.',
                ], 422);
            }

            $inventory->quantity = (float) $inventory->quantity - $amount;
        }

        $inventory->save();

        return response()->json([
            'message' => 'Inventory updated.',
            'quantity' => (float) $inventory->quantity,
        ]);
    }
}
