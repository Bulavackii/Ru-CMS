<?php

namespace Modules\Visual\Models;

use App\Support\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * 🔄 Инвалидация кэша при изменениях.
     *
     * Раньше здесь же писался ключ `active_theme_id` — ВТОРОЙ источник правды
     * рядом с колонкой `is_default`. Он записывался через forever, то есть сам
     * не протухал никогда, а getActive() читал его ПЕРВЫМ. Стоило двум
     * значениям разойтись — и сайт показывал тему, которой в базе не
     * применяли: база говорила одно, кеш другое, побеждал кеш. Ключ убран,
     * активная тема определяется только колонкой.
     */
    protected static function booted()
    {
        static::saved(fn ($theme) => static::flushActiveCache());
        static::deleted(fn ($theme) => static::flushActiveCache());
    }

    /** Ключ кеша активной темы. */
    private const ACTIVE_CACHE_KEY = 'active_theme';

    /**
     * 🧹 Сбросить всё, что закешировано об оформлении.
     *
     * Один метод на все места записи: раньше при сохранении темы забывался
     * только `active_theme_id`, а объект `active_theme` оставался — правка
     * темы доезжала до сайта лишь через час.
     */
    public static function flushActiveCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
        Cache::forget('active_theme_css');

        // Ключ прежней схемы: на работающих установках он уже лежит в кеше и
        // без этой строки продолжал бы перебивать базу до ручной чистки.
        Cache::forget('active_theme_id');

        foreach (available_locales() as $loc) {
            Cache::forget(self::LIST_CACHE_KEY . '_' . $loc);
        }
    }

    /**
     * ✅ Применить тему — сделать её активной для ВСЕГО сайта.
     *
     * Единственная точка применения: и кнопка «Применить» в разделе Темы, и
     * переключатель в шапке панели зовут этот метод. Раньше у них были две
     * независимые реализации, и они успели разойтись — переключатель в шапке
     * не пересобирал CSS темы, поэтому тема, применённая оттуда, выглядела
     * иначе, чем та же самая, применённая со страницы раздела.
     *
     * Признак активности — колонка `is_default`, она переживает и перезаход, и
     * очистку кеша, и перезапуск. Именно поэтому применённая тема остаётся у
     * всех: и у администраторов, и у обычных посетителей.
     */
    public static function apply(self $theme): void
    {
        DB::transaction(function () use ($theme) {
            static::where('id', '!=', $theme->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $theme->is_default = true;
            $theme->regenerateCss();
            $theme->save();
        });

        static::flushActiveCache();
    }

    /**
     * 🎨 Пересобрать CSS-переменные темы из её токенов.
     *
     * Жил приватным методом контроллера, отчего был недоступен переключателю в
     * шапке — см. пояснение к apply(). Место ему у самой темы: он описывает
     * её собственные данные.
     */
    public function regenerateCss(): void
    {
        $tokens = $this->tokens ?? [];
        $css = ':root{';

        foreach ((array) data_get($tokens, 'colors', []) as $name => $value) {
            if ($value !== null && $value !== '') {
                $css .= "--color-{$name}: {$value};";
            }
        }

        $css .= '--radius-md: ' . (string) data_get($tokens, 'radius.md', '12px') . ';';
        $css .= '--font-base: ' . (string) data_get(
            $tokens,
            'font.base',
            '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif'
        ) . ';';
        $css .= '}';

        // Прежний :root вырезаем, иначе они копятся дублями.
        $config = $this->config ?? [];
        $previous = preg_replace('/\:root\s*\{[^}]*\}\s*/m', '', (string) ($config['css'] ?? ''));
        $config['css'] = trim($previous . "\n" . $css);

        $this->config = $config;
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
     * 📦 Активная тема сайта.
     *
     * Источник ровно один — колонка `is_default`. Кеш здесь только копия
     * ответа базы, а не отдельное мнение: прежняя версия читала ключ
     * `active_theme_id` ПЕРЕД базой, и при расхождении сайт показывал тему,
     * которой не применяли. Проверено опытом: база `azure`, кеш `indigo` —
     * на экране был indigo.
     */
    public static function getActive(): ?self
    {
        return Cache::remember(
            self::ACTIVE_CACHE_KEY,
            3600,
            fn () => static::where('is_default', true)->first()
        );
    }
}
