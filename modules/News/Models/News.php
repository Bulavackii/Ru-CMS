<?php

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'content',
        'slug',
        'published',
        'template',
        'price',
        'stock',
        'is_promo',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_header',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published' => 'boolean',
        'is_promo' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope для получения только опубликованных новостей
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /**
     * Scope для получения новостей по шаблону
     */
    public function scopeByTemplate(Builder $query, string $template): Builder
    {
        return $query->where('template', $template);
    }

    /**
     * Scope для поиска по заголовку и содержимому
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Categories\Models\Category::class, 'news_category');
    }

    public function slideshow()
    {
        return $this->hasOne(\Modules\Slideshow\Models\Slideshow::class, 'news_id');
    }

    /**
     * Связь с пользователем, создавшим новость
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Связь с пользователем, обновившим новость
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
