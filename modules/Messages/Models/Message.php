<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content',
        'admin_id',
        'is_read',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
