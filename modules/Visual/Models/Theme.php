<?php

namespace Modules\Visual\Models;

use App\Support\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Theme extends Model
{
    use HasContentTranslations;

    /** Название темы видно посетителю в переключателе шапки */
    public array $translatable = ['title'];

    protected $table = 'visual_themes';
    protected $fillable = ['slug','title','tokens','config','is_default'];
    
    protected function casts(): array
    {
        return [
            'tokens' => 'array',
            'config' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * 🔄 Инвалидация кэша при изменениях
     */
    protected static function booted()
    {
        static::saved(function ($theme) {
            Cache::forget('active_theme');
            Cache::forget('active_theme_css');
            foreach (available_locales() as $loc) { Cache::forget(self::LIST_CACHE_KEY . '_' . $loc); }
            if ($theme->is_default) {
                Cache::forever('active_theme_id', $theme->id);
            }
        });

        static::deleted(function ($theme) {
            Cache::forget('active_theme');
            Cache::forget('active_theme_css');
            foreach (available_locales() as $loc) { Cache::forget(self::LIST_CACHE_KEY . '_' . $loc); }
            if (Cache::get('active_theme_id') == $theme->id) {
                Cache::forget('active_theme_id');
            }
        });
    }

    /** Ключ кеша со списком тем для переключателя в шапке сайта */
    private const LIST_CACHE_KEY = 'themes_public_list';

    /**
     * 🎚️ Список тем для переключателя на сайте.
     *
     * Берётся прямо из таблицы, поэтому добавленная в админке тема появляется
     * в шапке, а удалённая — исчезает. Кеш сбрасывается теми же хуками, что и
     * активная тема (см. booted выше), отдельного механизма не заводим.
     *
     * @return \Illuminate\Support\Collection<int, object{slug:string,title:string,primary:string,is_default:bool}>
     */
    public static function publicList()
    {
        // Язык в ключе: название темы переводится, у каждой локали свой список
        $key = self::LIST_CACHE_KEY . '_' . app()->getLocale();

        return Cache::remember($key, 3600, function () {
            return static::query()
                ->orderByDesc('is_default')
                ->orderBy('title')
                ->get(['id', 'slug', 'title', 'tokens', 'is_default'])
                ->map(fn (self $theme) => (object) [
                    'slug'       => $theme->slug,
                    'title'      => $theme->t('title'),
                    'primary'    => data_get($theme->tokens, 'colors.primary', '#6366f1'),
                    'is_default' => (bool) $theme->is_default,
                ])
                ->values();
        });
    }

    /**
     * 🙋 Тема для конкретного посетителя.
     *
     * Выбор посетителя — личный: он живёт в сессии и НЕ меняет активную тему
     * сайта. Если выбранную тему удалили из админки, молча возвращаемся к
     * активной, чтобы страница не падала и не оставалась без оформления.
     */
    public static function resolveForVisitor(?string $slug): ?self
    {
        if ($slug) {
            $theme = static::where('slug', $slug)->first();

            if ($theme) {
                return $theme;
            }
        }

        return static::getActive();
    }

    /**
     * 📦 Получить активную тему (с кэшированием)
     */
    public static function getActive()
    {
        return Cache::remember('active_theme', 3600, function () {
            $themeId = Cache::get('active_theme_id');
            if ($themeId) {
                return static::find($themeId);
            }
            return static::where('is_default', true)->first();
        });
    }
}
