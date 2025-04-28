<?php

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'version',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
