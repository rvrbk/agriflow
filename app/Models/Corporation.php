<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corporation extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'location',
        'address',
        'city',
        'state',
        'country',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'location' => 'array',
    ];
}
