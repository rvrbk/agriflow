<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * @return int|null
     */
    private function currentTenantId(): ?int
    {
        return \App\Models\Corporation::current()?->id;
    }

    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $tenantId = $this->currentTenantId();

        $inventoryRows = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('batches', 'inventories.batch_id', '=', 'batches.id')
            ->leftJoin('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->when($tenantId, fn ($query) => $query->where('inventories.corporation_id', $tenantId))
            ->orderByRaw("products.name->>'$.en'")
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
     * Sell inventory (subtract from stock).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sell(Request $request): JsonResponse
    {
        $tenantId = $this->currentTenantId();

        $validated = $request->validate([
            'batch_uuid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['string', 'in:USD,UGX'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $batch = Batch::query()
            ->where('uuid', $validated['batch_uuid'])
            ->when($tenantId, fn ($query) => $query->where('corporation_id', $tenantId))
            ->first();

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found.',
            ], 404);
        }

        $inventory = Inventory::query()
            ->where('batch_id', $batch->id)
            ->when($tenantId, fn ($query) => $query->where('corporation_id', $tenantId))
            ->first();

        if (!$inventory) {
            return response()->json([
                'message' => 'Inventory not found.',
            ], 404);
        }

        $amount = (float) $validated['amount'];

        if ((float) $inventory->quantity < $amount) {
            return response()->json([
                'message' => 'Insufficient inventory quantity.',
            ], 422);
        }

        $inventory->quantity = (float) $inventory->quantity - $amount;
        $inventory->save();

        // Record the sale
        $price = isset($validated['price']) ? (float) $validated['price'] : null;
        $currency = $validated['currency'] ?? 'USD';
        $totalValue = $price ? $amount * $price : null;

        $saleRecord = Sale::create([
            'batch_id' => $batch->id,
            'product_id' => $inventory->product_id,
            'warehouse_id' => $inventory->warehouse_id,
            'quantity' => $amount,
            'unit_price' => $price,
            'total_value' => $totalValue,
            'currency' => $currency,
            'buyer_name' => $validated['buyer_name'] ?? null,
        ]);

        // Load full sale data with relationships for receipt
        $saleData = Sale::with(['product', 'batch', 'warehouse'])->find($saleRecord->id);
        
        $productName = '';
        $productUnit = '';
        if ($saleData->product) {
            $name = $saleData->product->name ?? '';
            if (is_string($name)) {
                $names = json_decode($name, true);
                $productName = is_array($names) ? ($names['en'] ?? $name) : $name;
            }
            $productUnit = $saleData->product->unit ?? '';
        }

        return response()->json([
            'message' => 'Inventory sold.',
            'uuid' => $saleRecord->uuid,
            'quantity' => (float) $inventory->quantity,
            'total_value' => $totalValue,
            'sale_uuid' => $saleRecord->uuid,
            // Additional data for receipt
            'product_name' => $productName,
            'product_unit' => $productUnit,
            'batch_uuid' => $saleData->batch?->uuid ?? '',
            'harvested_on' => $saleData->batch?->harvested_on ?? null,
            'warehouse_name' => $saleData->warehouse?->name ?? null,
            'unit_price' => $price,
            'currency' => $currency,
            'quantity_sold' => $amount,
            'buyer_name' => $validated['buyer_name'] ?? null,
            'created_at' => $saleRecord->created_at->toISOString(),
        ]);
    }

    /**
     * Get sales history.
     *
     * @return JsonResponse
     */
    public function salesHistory(): JsonResponse
    {
        $sales = Sale::query()
            ->with(['product', 'batch', 'warehouse'])
            ->when($this->currentTenantId(), fn ($query, $tenantId) => $query->where('corporation_id', $tenantId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sale) {
                $productName = '';
                if ($sale->product) {
                    $name = $sale->product->name ?? '';
                    if (is_string($name)) {
                        $names = json_decode($name, true);
                        $productName = is_array($names) ? ($names['en'] ?? $name) : $name;
                    }
                }

                return [
                    'uuid' => $sale->uuid,
                    'product_name' => $productName ?: 'Unknown Product',
                    'product_unit' => $sale->product?->unit ?? '',
                    'batch_uuid' => $sale->batch?->uuid ?? '',
                    'warehouse_name' => $sale->warehouse?->name ?? '',
                    'quantity' => $sale->quantity,
                    'unit_price' => $sale->unit_price,
                    'currency' => $sale->currency ?? 'USD',
                    'total_value' => $sale->total_value,
                    'created_at' => $sale->created_at,
                ];
            });

        return response()->json($sales);
    }

    /**
     * Get a single sale by UUID for receipt display.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function getSale(string $uuid): JsonResponse
    {
        $sale = Sale::with(['product', 'batch', 'warehouse'])
            ->when($this->currentTenantId(), fn ($query, $tenantId) => $query->where('corporation_id', $tenantId))
            ->where('uuid', $uuid)
            ->first();

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found.',
            ], 404);
        }

        $productName = '';
        if ($sale->product) {
            $name = $sale->product->name ?? '';
            if (is_string($name)) {
                $names = json_decode($name, true);
                $productName = is_array($names) ? ($names['en'] ?? $name) : $name;
            }
        }

        return response()->json([
            'uuid' => $sale->uuid,
            'buyer_name' => $sale->buyer_name,
            'product_name' => $productName,
            'product_unit' => $sale->product?->unit ?? '',
            'batch_uuid' => $sale->batch?->uuid ?? '',
            'harvested_on' => $sale->batch?->harvested_on ?? null,
            'warehouse_name' => $sale->warehouse?->name ?? '',
            'quantity' => $sale->quantity,
            'unit_price' => $sale->unit_price,
            'total_value' => $sale->total_value,
            'currency' => $sale->currency ?? 'USD',
            'created_at' => $sale->created_at,
        ]);
    }
}
