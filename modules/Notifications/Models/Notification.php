<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'target',
        'position',
        'duration',
        'icon',
        'route_filter',
        'cookie_key',
        'enabled',
        'bg_color',
        'text_color', 
    ];
}
