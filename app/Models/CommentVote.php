<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 💬 Модель голоса за комментарий
 */
class CommentVote extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'ip_address',
        'vote',
    ];

    /**
     * Комментарий
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Пользователь
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

