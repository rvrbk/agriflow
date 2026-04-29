<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\Corporation;
use Illuminate\Support\Str;

class WarehouseService
{
    public const DELETE_DELETED = 'deleted';
    public const DELETE_NOT_FOUND = 'not_found';
    public const DELETE_LINKED_TO_INVENTORY = 'linked_to_inventory';

    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        $currentTenant = Corporation::current();

        foreach ($data as $row) {
            $warehouse = null;

            if (isset($row['uuid'])) {
                $warehouse = Warehouse::where('uuid', $row['uuid'])->first();
            }

            if (!$warehouse) {
                $warehouse = new Warehouse();

                $warehouse->uuid = Str::uuid();
            }

            if ($currentTenant) {
                $warehouse->corporation_id = $currentTenant->id;
            }

            if (! $currentTenant && isset($row['corporation_uuid'])) {
                $corporation = Corporation::where('uuid', $row['corporation_uuid'])->first();

                if ($corporation) {
                    $warehouse->corporation_id = $corporation->id;
                }
            }

            $warehouse->name = $row['name'];
            $warehouse->location = isset($row['location']) ? json_encode($row['location']) : null;
            $warehouse->address = $row['address'] ?? null;
            $warehouse->city = $row['city'] ?? null;
            $warehouse->state = $row['state'] ?? null;
            $warehouse->country = $row['country'] ?? null;

            $warehouse->save();
        }
    }

    /**
     * @param string $uuid
     * @return string
     */
    public function deleteByUuid(string $uuid): string
    {
        $warehouse = Warehouse::query()->where('uuid', $uuid)->first();

        if (!$warehouse) {
            return self::DELETE_NOT_FOUND;
        }

        $hasInventoryLinks = Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->exists();

        if ($hasInventoryLinks) {
            return self::DELETE_LINKED_TO_INVENTORY;
        }

        $warehouse->delete();

        return self::DELETE_DELETED;
    }
}