<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 📝 Модель черновика контента
 */
class ContentDraft extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'data',
        'key',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'saved_at' => 'datetime',
        ];
    }

    /**
     * Автоматическая генерация ключа
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($draft) {
            if (!$draft->key) {
                $draft->key = Str::random(32);
            }
            if (!$draft->saved_at) {
                $draft->saved_at = now();
            }
        });
    }

    /**
     * Пользователь
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Сохранить или обновить черновик
     */
    public static function saveDraft(string $modelType, ?int $modelId, array $data, ?int $userId = null): self
    {
        $userId = $userId ?? auth()->id();

        return self::updateOrCreate(
            [
                'model_type' => $modelType,
                'model_id' => $modelId,
                'user_id' => $userId,
            ],
            [
                'data' => $data,
                'saved_at' => now(),
            ]
        );
    }

    /**
     * Получить черновик по ключу
     */
    public static function findByKey(string $key): ?self
    {
        return self::where('key', $key)->first();
    }

    /**
     * Очистить старые черновики (старше 30 дней)
     */
    public static function cleanupOldDrafts(int $days = 30): int
    {
        return self::where('saved_at', '<', now()->subDays($days))->delete();
    }
}

