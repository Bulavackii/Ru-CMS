<?php

namespace Modules\Categories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * 🗂️ Модель категории
 *
 * Представляет запись в таблице `categories`.
 * Используется для привязки к записям, продуктам, FAQ и другим сущностям.
 * Поддерживает иерархию категорий (родитель-потомок).
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ✅ Разрешённые для массового заполнения (Mass Assignment) поля
     *
     * Здесь перечисляем только те поля, которые можно безопасно заполнять
     * через `create()`, `update()` и подобные методы.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    /**
     * 🔄 Преобразование типов атрибутов
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 🎯 Boot метод для автоматической генерации slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug) && !empty($category->title)) {
                $category->slug = Str::slug($category->title);
                // Убедимся, что slug уникален
                $originalSlug = $category->slug;
                $counter = 1;
                while (static::where('slug', $category->slug)->exists()) {
                    $category->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('title') && empty($category->slug)) {
                $category->slug = Str::slug($category->title);
                // Убедимся, что slug уникален (исключая текущую категорию)
                $originalSlug = $category->slug;
                $counter = 1;
                while (static::where('slug', $category->slug)
                    ->where('id', '!=', $category->id)
                    ->exists()) {
                    $category->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * 🔗 Связь многие-ко-многим с новостями
     *
     * @return BelongsToMany
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\News\Models\News::class, 'news_category');
    }

    /**
     * 🔗 Связь многие-ко-многим со страницами
     *
     * @return BelongsToMany
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Menu\Models\Page::class, 'page_category');
    }

    /**
     * 🔗 Родительская категория
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 🔗 Дочерние категории
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 📊 Scope: фильтрация по типу
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * 📊 Scope: только активные категории
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 📊 Scope: только корневые категории (без родителя)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 📊 Scope: сортировка по порядку и названию
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * 📊 Scope: поиск по названию или описанию
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    /**
     * 📊 Scope: категории с потомками
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithChildren(Builder $query): Builder
    {
        return $query->with('children');
    }

    /**
     * 📊 Scope: категории с родителем
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithParent(Builder $query): Builder
    {
        return $query->with('parent');
    }

    /**
     * 🔍 Получить полный путь категории (с родителями)
     *
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->title];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->title);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * 🔍 Проверить, используется ли категория (связана ли с другими записями)
     *
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->news()->exists() || 
               $this->pages()->exists() || 
               $this->children()->exists();
    }

    /**
     * 📊 Получить количество связанных записей
     *
     * @return array
     */
    public function getUsageCounts(): array
    {
        return [
            'news' => $this->news()->count(),
            'pages' => $this->pages()->count(),
            'children' => $this->children()->count(),
        ];
    }
}
