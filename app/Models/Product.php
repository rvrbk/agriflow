<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use BelongsToCurrentTenant, HasTranslations;

    protected $fillable = [
        'uuid',
        'corporation_id',
        'name',
        'code',
        'code_type',
        'unit',
    ];

    public $translatable = ['name'];
}
