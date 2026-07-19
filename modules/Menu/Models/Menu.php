<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'title',
        'position',
        'active',
    ];

    /** Eager: пункты меню по полю order */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /** Активные пункты меню */
    public function activeItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->where('active', true)
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    /* ── Удобные скоупы ───────────────────────────────────────── */

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    public function scopePosition($q, string $position)
    {
        return $q->where('position', $position);
    }

    /* ── Кеш: helpers + авто-инвалидация ─────────────────────── */

    /** Быстрый доступ с кешем по позиции */
    public static function cachedByPosition(string $position, int $minutes = 60)
    {
        $key = "menu.$position";
        return Cache::tags(['menus'])->remember($key, $minutes * 60, function () use ($position) {
            return static::query()
                ->active()
                ->position($position)
                ->with([
                    'items' => fn($q) => $q->where('active', true)->whereNull('parent_id')->orderBy('order'),
                    'items.activeChildren' => fn($q) => $q->where('active', true)->orderBy('order'),
                    'items.linkedPage',
                ])
                ->get();
        });
    }

    /** Сброс кеша для всех стандартных позиций */
    public static function flushCache(): void
    {
        // Используем теги кэша для более эффективной инвалидации
        Cache::tags(['menus'])->flush();
        
        // Также очищаем старые ключи для обратной совместимости
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
        Cache::forget('menu.sidebar');
    }

    protected static function booted()
    {
        static::saved(fn() => static::flushCache());
        static::deleted(fn() => static::flushCache());
    }
}
