<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Corporation;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
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
            if (!is_array($row)) {
                continue;
            }

            $product = $this->resolveModelByUuid(Product::class, $row['product_uuid'] ?? null);
            $warehouse = $this->resolveModelByUuid(Warehouse::class, $row['warehouse_uuid'] ?? null);

            if (!$product || !$warehouse) {
                continue;
            }

            $batch = null;
            $isNewBatch = false;

            if (isset($row['batch_uuid']) && is_string($row['batch_uuid'])) {
                $batch = Batch::where('uuid', $row['batch_uuid'])->first();

                if (!$batch) {
                    continue;
                }
            }

            if (!$batch) {
                $batch = new Batch();
                $batch->uuid = (string) Str::uuid();
                $isNewBatch = true;
            }

            $corporation = $this->resolveModelByUuid(Corporation::class, $row['corporation_uuid'] ?? null);

            if (!$corporation && $isNewBatch) {
                continue;
            }

            if ($corporation) {
                $batch->corporation_id = $corporation->id;
            }

            $batch->harvested_on = $row['harvested_on'] ?? ($batch->harvested_on ?? now());
            $batch->expires_on = array_key_exists('expires_on', $row) ? $row['expires_on'] : $batch->expires_on;
            $batch->quality = $row['quality'] ?? $batch->quality;

            if (!empty($row['regenerate_qr']) || !$batch->qr_code) {
                $batch->qr_code = $this->generateQrCode();
            }

            if (!empty($row['regenerate_qr']) || !$batch->qr_payload || !empty($row['qr_payload'])) {
                $batch->qr_payload = $this->buildQrPayload($batch, $row);
            }

            if (array_key_exists('qr_code', $row) && is_string($row['qr_code']) && $row['qr_code'] !== '') {
                $batch->qr_code = $row['qr_code'];
            }

            $batch->save();

            $inventory = Inventory::where('batch_id', $batch->id)->first();
            $isNewInventory = !$inventory;

            if (!$inventory) {
                $inventory = new Inventory();
                $inventory->batch_id = $batch->id;
            }

            $inventory->product_id = $product->id;
            $inventory->warehouse_id = $warehouse->id;

            if (array_key_exists('available_on', $row)) {
                $inventory->available_on = $row['available_on'];
            } elseif ($isNewInventory) {
                $inventory->available_on = now();
            }

            $inventory->location = $row['location'] ?? null;

            if (array_key_exists('quantity', $row)) {
                $quantity = (float) $row['quantity'];
                $replaceQuantity = (bool) ($row['replace_quantity'] ?? false);

                if ($inventory->exists && !$replaceQuantity) {
                    $inventory->quantity = (float) $inventory->quantity + $quantity;
                } else {
                    $inventory->quantity = $quantity;
                }
            }

            $inventory->save();
        }
    }

    /**
     * @param string $batchUuid
     * @return bool
     */
    public function deleteByBatchUuid(string $batchUuid): bool
    {
        $batch = Batch::where('uuid', $batchUuid)->first();

        if (!$batch) {
            return false;
        }

        Inventory::where('batch_id', $batch->id)->delete();

        return (bool) $batch->delete();
    }

    /**
     * @return string
     */
    private function generateQrCode(): string
    {
        return 'HARV-' . strtoupper(Str::random(12));
    }

    /**
     * @param Batch $batch
     * @param array $row
     * @return string
     */
    private function buildQrPayload(Batch $batch, array $row): string
    {
        if (array_key_exists('qr_payload', $row) && is_string($row['qr_payload']) && $row['qr_payload'] !== '') {
            return $row['qr_payload'];
        }

        return url('/harvest/' . $batch->uuid);
    }

    /**
     * @param class-string<Model> $modelClass
     * @param mixed $uuid
     * @return Model|null
     */
    private function resolveModelByUuid(string $modelClass, mixed $uuid): ?Model
    {
        if (!is_string($uuid) || $uuid === '') {
            return null;
        }

        return $modelClass::where('uuid', $uuid)->first();
    }
}