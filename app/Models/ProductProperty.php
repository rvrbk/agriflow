<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductProperty extends Model
{
    use HasTranslations;

    public $translatable = ['key', 'value'];
}
