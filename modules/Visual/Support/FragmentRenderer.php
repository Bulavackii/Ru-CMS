<?php

namespace Modules\Visual\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Visual\Models\Fragment;

/**
 * 🧩 Вывод фрагментов в зонах страниц.
 *
 * Контракт: методы возвращают null, когда выводить нечего (нет фрагмента, он
 * выключен или пуст). Раньше render() отдавал HTML-комментарий вроде
 * «<!-- visual: fragment not found -->», а лейауты искали в ответе подстроку
 * 'fragment not found' — из-за такой проверки на страницу мог попасть мусорный
 * комментарий, а любое изменение текста ошибки её ломало.
 */
class FragmentRenderer
{
    /** Сколько держим собранный HTML зоны в кеше (секунды) */
    private const TTL = 300;

    /**
     * Активный фрагмент по slug или зоне.
     */
    public static function find(array $opts = []): ?Fragment
    {
        $slug = $opts['slug'] ?? null;
        $zone = $opts['zone'] ?? null;

        if (!$slug && !$zone) {
            return null;
        }

        try {
            if (!Schema::hasTable('visual_fragments')) {
                return null;
            }

            $query = Fragment::query()->where('is_active', true);

            $slug ? $query->where('slug', $slug) : $query->where('zone', $zone);

            return $query->latest('updated_at')->first();
        } catch (\Throwable $e) {
            // Страница не должна падать из-за фрагментов
            return null;
        }
    }

    /**
     * HTML фрагмента или null, если выводить нечего.
     */
    public static function render(array $opts = []): ?string
    {
        $fragment = $opts['fragment'] ?? self::find($opts);

        if (!$fragment) {
            return null;
        }

        // 1) Готовый HTML из админки — самый частый и быстрый путь
        $html = trim((string) $fragment->html_cached);

        // 2) Иначе — собственная вьюха по конвенции visual/fragments/{slug}
        if ($html === '') {
            $viewName = $opts['view'] ?? ('visual.fragments.' . $fragment->slug);

            if (View::exists($viewName)) {
                $html = trim(view($viewName, array_merge(
                    ['fragment' => $fragment],
                    $opts['data'] ?? []
                ))->render());
            }
        }

        if ($html === '') {
            return null;
        }

        $css = trim((string) $fragment->css_inline);

        return $css !== ''
            ? '<style data-fragment="' . e($fragment->slug) . '">' . $css . '</style>' . $html
            : $html;
    }

    /**
     * HTML зоны с кешированием — то, что вызывают лейауты.
     *
     * Кеш сбрасывается при любом сохранении и удалении фрагмента
     * (см. Fragment::booted), поэтому правка сразу видна на сайте.
     */
    public static function zone(string $zone): ?string
    {
        try {
            $key = 'fragment_zone_v' . Fragment::cacheVersion() . '_' . $zone;

            return Cache::remember($key, self::TTL, fn () => self::render(['zone' => $zone]));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
