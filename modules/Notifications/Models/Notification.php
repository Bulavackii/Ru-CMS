<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * 💾 Массово заполняемые поля (для методов create/update)
     */
    protected $fillable = [
        'title',        // 📌 Заголовок уведомления
        'message',      // 💬 Основной текст (HTML/TinyMCE)
        'type',         // 📋 Тип (text | html | cookie)
        'target',       // 🎯 Целевая аудитория (all | admin | user)
        'position',     // 📍 Расположение (top | bottom | fullscreen)
        'duration',     // ⏱️ Время показа (в секундах, 0 = бесконечно)
        'icon',         // 🖼️ Иконка (emoji или FontAwesome)
        'route_filter', // 🗺️ URL или имя маршрута (для фильтрации)
        'cookie_key',   // 🍪 Ключ cookie (если type = cookie)
        'enabled',      // ✅ Включено или нет
        'bg_color',     // 🎨 Цвет фона (HEX)
        'text_color',   // 🖋️ Цвет текста (HEX)
        'priority',     // 📊 Приоритет (для сортировки)
        'starts_at',    // 🕐 Начало показа
        'ends_at',      // 🕐 Конец показа
        'views_count',  // 👁️ Количество показов
        'created_by',   // 👤 Создатель
        'updated_by',   // 👤 Обновивший
    ];

    /**
     * 🔧 Кастинг типов
     */
    protected $casts = [
        'enabled' => 'boolean',
        'duration' => 'integer',
        'priority' => 'integer',
        'views_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope для получения только включенных уведомлений
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope для фильтрации по целевой аудитории
     */
    public function scopeForTarget(Builder $query, ?string $target, $user = null): Builder
    {
        if ($target === 'all') {
            return $query->where('target', 'all');
        }

        if ($target === 'admin' && $user && $user->is_admin) {
            return $query->where(function ($q) {
                $q->where('target', 'all')
                  ->orWhere('target', 'admin');
            });
        }

        if ($target === 'user' && $user && !$user->is_admin) {
            return $query->where(function ($q) {
                $q->where('target', 'all')
                  ->orWhere('target', 'user');
            });
        }

        if (!$user) {
            return $query->where('target', 'all');
        }

        return $query->where('target', $target);
    }

    /**
     * Scope для фильтрации по маршруту
     */
    public function scopeForRoute(Builder $query, ?string $route, ?string $url): Builder
    {
        return $query->where(function ($q) use ($route, $url) {
            $q->whereNull('route_filter')
              ->orWhere('route_filter', $url)
              ->orWhere('route_filter', $route);
        });
    }

    /**
     * Scope для поиска по заголовку и содержимому
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%");
        });
    }

    /**
     * Scope для фильтрации по типу
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope для фильтрации по позиции
     */
    public function scopeByPosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    /**
     * Scope для активных уведомлений (включенных и в периоде показа)
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Связь с пользователем, создавшим уведомление
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Связь с пользователем, обновившим уведомление
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
