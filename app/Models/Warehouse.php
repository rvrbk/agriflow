<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTenant;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToCurrentTenant;
}
