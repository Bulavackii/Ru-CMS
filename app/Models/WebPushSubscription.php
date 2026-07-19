<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 📱 Модель Web Push подписки
 */
class WebPushSubscription extends Model
{
    protected $table = 'web_push_subscriptions';

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'user_agent',
        'ip_address',
        'active',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    /**
     * Пользователь подписки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверка валидности подписки
     */
    public function isValid(): bool
    {
        return $this->active && 
               !empty($this->endpoint) && 
               !empty($this->public_key) && 
               !empty($this->auth_token);
    }
}

