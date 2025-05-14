<?php

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryMethod extends Model
{
    protected $fillable = ['title', 'description', 'price', 'active'];
}
