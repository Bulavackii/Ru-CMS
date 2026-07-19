<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 📊 Модель просмотра контента
 */
class ContentView extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'ip_address',
        'user_agent',
        'referer',
        'user_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Полиморфная связь с контентом
     */
    public function viewable(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Пользователь, который просмотрел
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Записать просмотр
     */
    public static function record($model, ?int $userId = null): self
    {
        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'user_id' => $userId ?? auth()->id(),
            'viewed_at' => now(),
        ]);
    }
}

