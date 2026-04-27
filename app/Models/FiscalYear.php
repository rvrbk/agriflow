<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'corporation_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_closed',
        'closed_at',
        'closed_by',
        'total_revenue',
        'total_expenses',
        'net_profit_loss',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'total_revenue' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit_loss' => 'decimal:2',
        'notes' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });

        // Auto-update totals when a sale is created/updated/deleted
        static::saved(function ($fiscalYear) {
            $fiscalYear->updateTotals();
        });
    }

    public function corporation()
    {
        return $this->belongsTo(Corporation::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'fiscal_year_id');
    }

    public function updateTotals()
    {
        $totalRevenue = $this->sales()->sum('total_value') ?? 0;
        $netProfitLoss = $totalRevenue;

        $this->update([
            'total_revenue' => $totalRevenue,
            'total_expenses' => 0,
            'net_profit_loss' => $netProfitLoss,
        ]);
    }

    /**
     * Scope to get active fiscal year
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_closed', false);
    }

    /**
     * Get current active fiscal year for a corporation
     */
    public static function getActiveForCorporation($corporationId)
    {
        return self::where('corporation_id', $corporationId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();
    }
}
