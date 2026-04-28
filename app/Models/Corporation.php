<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;

class Corporation extends Model implements IsTenant
{
    use UsesMultitenancyConfig;
    use ImplementsTenant;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'domain',
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
