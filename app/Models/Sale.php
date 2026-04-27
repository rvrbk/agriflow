<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'batch_id',
        'product_id',
        'warehouse_id',
        'fiscal_year_id',
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

            // Auto-assign fiscal year based on batch's corporation
            if (!$model->fiscal_year_id && $model->batch_id) {
                $batch = Batch::find($model->batch_id);
                if ($batch && $batch->corporation_id) {
                    $activeFiscalYear = FiscalYear::getActiveForCorporation($batch->corporation_id);
                    if ($activeFiscalYear) {
                        $model->fiscal_year_id = $activeFiscalYear->id;
                    }
                }
            }
        });

        // Update fiscal year totals when sale is created/updated
        static::saved(function ($sale) {
            if ($sale->fiscal_year_id) {
                $fiscalYear = FiscalYear::find($sale->fiscal_year_id);
                if ($fiscalYear) {
                    $fiscalYear->updateTotals();
                }
            }
        });

        // Update fiscal year totals when sale is deleted
        static::deleted(function ($sale) {
            if ($sale->fiscal_year_id) {
                $fiscalYear = FiscalYear::find($sale->fiscal_year_id);
                if ($fiscalYear) {
                    $fiscalYear->updateTotals();
                }
            }
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

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
