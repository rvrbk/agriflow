<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use BelongsToCurrentTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'corporation_id',
        'batch_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_price',
        'total_value',
        'currency',
        'notes',
        'buyer_name',
        'buyer_info',
    ];

    protected $casts = [
        'notes' => 'array',
        'total_value' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

}
