<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'user_id',
        'to_user_id',
        'subject',
        'body',
        'is_read',
    ];

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(\App\Models\User::class, 'to_user_id');
    }
}
