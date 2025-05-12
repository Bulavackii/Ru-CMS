<?php

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'version',
        'active',
        'installed_at',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $dates = ['installed_at'];
}
