<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'title',
        'description',
        'type',
        'active',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'active' => 'boolean',
    ];
}
