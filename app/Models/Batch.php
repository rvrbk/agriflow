<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'corporation_id',
        'harvested_on',
        'expires_on',
        'quality',
        'qr_code',
        'qr_payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'harvested_on' => 'datetime',
        'expires_on' => 'datetime',
    ];
}
