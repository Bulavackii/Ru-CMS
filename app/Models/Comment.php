<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 💬 Модель комментария
 */
class Comment extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
        'parent_id',
        'status',
        'likes',
        'dislikes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'likes' => 'integer',
            'dislikes' => 'integer',
        ];
    }

    /**
     * Полиморфная связь с контентом
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Автор комментария
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Родительский комментарий
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Дочерние комментарии (ответы)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('status', 'approved')->orderBy('created_at');
    }

    /**
     * Все дочерние комментарии (включая неодобренные)
     */
    public function allReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Голоса (лайки/дизлайки)
     */
    public function votes()
    {
        return $this->hasMany(CommentVote::class);
    }

    /**
     * Проверка, одобрен ли комментарий
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Проверка, является ли комментарий ответом
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Получить имя автора
     */
    public function getAuthorNameAttribute($value): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        return $value ?? 'Гость';
    }

    /**
     * Получить email автора
     */
    public function getAuthorEmailAttribute($value): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }
        return $value;
    }
}

