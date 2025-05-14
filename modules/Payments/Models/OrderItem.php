<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'title',
        'price',
        'qty',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
