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

    /**
     * Поддерживает ли текущий кэш-стор теги.
     *
     * Теги умеют только array/redis/memcached (наследники TaggableStore).
     * Боевые file/database — НЕ умеют и бросают BadMethodCallException
     * «This cache store does not support tagging». В тестах стор array
     * (taggable), поэтому проблема ловилась только вживую на проде.
     */
    protected static function cacheSupportsTags(): bool
    {
        return Cache::getStore() instanceof \Illuminate\Cache\TaggableStore;
    }

    /** Быстрый доступ с кешем по позиции */
    public static function cachedByPosition(string $position, int $minutes = 60)
    {
        $key = "menu.$position";
        $ttl = $minutes * 60;
        $builder = function () use ($position) {
            return static::query()
                ->active()
                ->position($position)
                ->with([
                    'items' => fn($q) => $q->where('active', true)->whereNull('parent_id')->orderBy('order'),
                    'items.activeChildren' => fn($q) => $q->where('active', true)->orderBy('order'),
                    'items.linkedPage',
                ])
                ->get();
        };

        // На taggable-сторе — с тегом (эффективная групповая инвалидация),
        // иначе обычный remember по тому же ключу (его снимет flushCache()).
        return static::cacheSupportsTags()
            ? Cache::tags(['menus'])->remember($key, $ttl, $builder)
            : Cache::remember($key, $ttl, $builder);
    }

    /** Сброс кеша для всех стандартных позиций */
    public static function flushCache(): void
    {
        // Групповая инвалидация по тегу — только если стор её поддерживает,
        // иначе Cache::tags() роняет 500 на любом save/delete/toggle меню.
        if (static::cacheSupportsTags()) {
            Cache::tags(['menus'])->flush();
        }

        // Точечная инвалидация известных позиций — работает на ЛЮБОМ сторе
        // (и как fallback для file/database, и для обратной совместимости).
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
