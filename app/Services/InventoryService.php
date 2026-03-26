<?php

namespace App\Services;

use App\Enums\UnitEnum;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class InventoryService
{
    /**
     * @param array $data
     * @return bool
     */
    public function store(array $data): bool
    {
        $inventory = new Inventory();

        $inventory->created_by = Auth::user()->id;
        $inventory->updated_by = Auth::user()->id;
        $inventory->product_id = 3;
        $inventory->increment('quantity', 5);
        $inventory->unit = UnitEnum::KG->value;

        return $inventory->save();
    }
}