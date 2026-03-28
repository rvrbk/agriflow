<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InventoryService
{
    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        $user = Auth::user();

        foreach ($data as $row) {
            $inventory = Inventory::where('product_id', $row['product_id'])->first();

            if (!$inventory) {
                $inventory = new Inventory();

                $inventory->created_by = $user->id;
                $inventory->uuid = Str::uuid();
            }

            $inventory->updated_by = $user->id;

            if (!isset($row['available_on'])) {
                $inventory->available_on = now();
            }

            $inventory->increment('quantity', $row['quantity']);

            $inventory->save();
        }
    }
}