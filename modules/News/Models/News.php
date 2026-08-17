<?php

namespace Modules\News\Models;

use App\Support\HasContentTranslations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class News extends Model
{
    use HasContentTranslations;

    /** Поля, которые можно перевести на другие языки (см. трейт) */
    public array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'content',
        'slug',
        'published',
        'show_on_homepage',
        'homepage_order',
        'template',
        'price',
        'rating',
        'stock',
        // Вес в килограммах. Нужен службам доставки: у них есть ограничение
        // `weight_limit`, и без веса товара его не применить.
        'weight',
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
        'show_on_homepage' => 'boolean',
        'homepage_order' => 'integer',
        'is_promo' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
        // ⚠️ Пустой вес — это «не взвешиваем» (услуга, цифровой товар), а не
        // ноль. Заказ из одних услуг ограничение по весу проходить не должен.
        'weight' => 'decimal:3',
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

    /**
     * Версия содержимого — часть ключа кеша блоков главной.
     *
     * Главная держит каждый блок в кеше пять минут, а сохранение материала
     * кеш не сбрасывало: правка заголовка или оценки доезжала до сайта с
     * задержкой до пяти минут, и выглядело это как «в панели сохранилось,
     * на сайте нет».
     *
     * Полный Cache::flush() здесь не подходит: он снёс бы кеш всего
     * приложения ради одного раздела. Поэтому меняем не кеш, а ключ — старые
     * записи протухнут сами.
     *
     * (Прежде здесь стояла ссылка на ключ active_theme_id, живший вечно. Его
     * больше нет: активную тему определяет колонка в базе.)
     */
    public static function contentVersion(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::get('news_content_version', 1);
    }

    public static function bumpContentVersion(): void
    {
        \Illuminate\Support\Facades\Cache::forever(
            'news_content_version',
            self::contentVersion() + 1
        );
    }

    protected static function booted(): void
    {
        // Любое изменение материала — новая версия ключа.
        //
        // ⚠️ Слушать `saved` НЕДОСТАТОЧНО, и это стоило неверных остатков на
        // сайте. `decrement()`/`increment()` — а именно ими списывается товар
        // при покупке и возвращается при удалении заказа — событие `saved` НЕ
        // поднимают: Eloquent в `incrementOrDecrement()` дёргает только
        // `updating` и `updated` (проверено замером на этой версии). Версия
        // ключа не менялась, блок главной жил своей жизнью ещё пять минут, и
        // раскупленный товар всё это время показывался как «в наличии».
        //
        // `created` + `updated` покрывают ровно то же, что `saved`, плюс оба
        // счётчика остатка.
        static::created(fn () => self::bumpContentVersion());
        static::updated(fn () => self::bumpContentVersion());
        static::deleted(fn () => self::bumpContentVersion());
    }
}
