<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTenant;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use BelongsToCurrentTenant;

    protected $fillable = [
        'corporation_id',
        'product_id',
        'warehouse_id',
        'batch_id',
        'quantity',
        'location',
        'available_on',
    ];
}
