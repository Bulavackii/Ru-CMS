<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'url',
        'linked_id',
        'order',
        'active',
        'icon',
        'css_class',
        'target',
        'rel',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('active', true)
            ->orderBy('order');
    }

    /**
     * Получить глубину вложенности пункта меню
     * 
     * @return int Глубина (0 = корневой уровень)
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        
        return $depth;
    }

    /**
     * Проверить, можно ли добавить дочерний элемент
     * (максимум 3 уровня вложенности)
     * 
     * @return bool
     */
    public function canHaveChildren(): bool
    {
        return $this->getDepth() < 2; // 0, 1, 2 = максимум 3 уровня
    }

    /**
     * Scope для активных пунктов
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(\Modules\Menu\Models\Page::class, 'linked_id');
    }

    public function linkedCategory(): BelongsTo
    {
        return $this->belongsTo(\Modules\Categories\Models\Category::class, 'linked_id');
    }

    /* Автосброс кеша при изменениях пунктов меню */
    protected static function booted()
    {
        $flush = fn() => \Modules\Menu\Models\Menu::flushCache();
        static::saved($flush);
        static::deleted($flush);
    }
}
