<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\Corporation;
use Illuminate\Support\Str;

class WarehouseService
{
    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        foreach ($data as $row) {
            $warehouse = null;

            if (isset($row['uuid'])) {
                $warehouse = Warehouse::where('uuid', $row['uuid'])->first();
            }

            if (!$warehouse) {
                $warehouse = new Warehouse();

                $warehouse->uuid = Str::uuid();
            }

            if (isset($row['corporation_uuid'])) {
                $corporation = Corporation::where('uuid', $row['corporation_uuid'])->first();

                if ($corporation) {
                    $warehouse->corporation_id = $corporation->id;
                }
            }

            $warehouse->name = $row['name'];
            $warehouse->location = isset($row['location']) ? json_encode($row['location']) : null;
            $warehouse->capacity = $row['capacity'] ?? null;
            $warehouse->address = $row['address'] ?? null;
            $warehouse->city = $row['city'] ?? null;
            $warehouse->state = $row['state'] ?? null;
            $warehouse->country = $row['country'] ?? null;

            $warehouse->save();
        }
    }
}