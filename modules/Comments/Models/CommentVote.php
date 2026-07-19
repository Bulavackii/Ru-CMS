<?php

namespace Modules\Comments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 💬 Модель голоса за комментарий
 */
class CommentVote extends Model
{
    protected $table = 'comment_votes';

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
        return $this->belongsTo(\App\Models\User::class);
    }
}

