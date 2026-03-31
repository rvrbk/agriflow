<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Corporation;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class HarvestService
{
    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        foreach ($data as $row) {
            $product = Product::where('uuid', $row['product_uuid'])->first();

            if (!$product) {
                continue;
            }

            $inventory = Inventory::where('product_id', $product->id)->first();

            if (!$inventory) {
                $warehouse = Warehouse::where('uuid', $row['warehouse_uuid'])->first();

                if (!$warehouse) {
                    continue;
                }

                $corporation = Corporation::where('uuid', $row['corporation_uuid'])->first();

                if (!$corporation) {
                    continue;
                }

                $batch = new Batch();

                if (isset($row['batch_uuid'])) {
                    $batch = Batch::where('uuid', $row['batch_uuid'])->first();

                    if (!$batch) {
                        continue;
                    }
                } else {
                    $batch->uuid = Str::uuid();
                }

                $batch->corporation_id = $corporation->id;
                $batch->harvested_on = isset($row['harvested_on']) ? $row['harvested_on'] : now();
                $batch->expires_on = isset($row['expires_on']) ? $row['expires_on'] : null;
                $batch->quality = $row['quality'] ?? null;

                $batch->save();

                $inventory = new Inventory();

                $inventory->batch_id = $batch->id;
                $inventory->product_id = $product->id;
                $inventory->warehouse_id = $warehouse->id;
            }

            if (!isset($row['available_on'])) {
                $inventory->available_on = now();
            }

            $inventory->location = $row['location'] ?? null;

            if (!$inventory->exists) {
                $inventory->quantity = $row['quantity'];
            } else {
                $inventory->increment('quantity', $row['quantity']);
            }

            $inventory->save();
        }
    }
}