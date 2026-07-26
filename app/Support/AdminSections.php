<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * 🗺️ Разделы панели — единый источник правды.
 *
 * Раньше список разделов жил только в разметке сайдбара
 * (layouts/admin/sidebar.blade.php), поэтому глобальный поиск про разделы
 * не знал вообще: искать «Темы» или «Локализация» было негде — поиск
 * ходил только по содержимому (новости/страницы/категории/пользователи/меню).
 * Теперь и навигация, и поиск читают один и тот же список.
 *
 * `keywords` — синонимы для поиска: раздел должен находиться и по слову,
 * которого нет в его названии («оформление» → Темы, «перевод» → Локализация).
 */
class AdminSections
{
    /**
     * Разделы по смысловым группам — в том порядке, в котором их показывает
     * сайдбар. Пункты необязательных модулей отсеиваются по Route::has().
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function groups(): array
    {
        $groups = [
            __('admin.section_groups.content') => [
                self::link('menus', 'admin.menus.index', 'dashboard', 'admin.menus.*'),
                self::link('news', 'admin.news.index', 'file-text', 'admin.news.*'),
                self::link('pages', 'admin.pages.index', 'file-text', 'admin.pages.*'),
                self::link('categories', 'admin.categories.index', 'folder', 'admin.categories.*'),
                self::link('slideshow', 'admin.slideshow.index', 'image', 'admin.slideshow.*'),
                self::link('files', 'admin.files.index', 'folder', 'admin.files.*'),
                self::link('newsio', 'admin.newsio.index', 'arrow-up', 'admin.newsio.*'),
            ],

            __('admin.section_groups.system') => [
                self::url('modules', '/admin/modules', 'puzzle', 'admin/modules'),
                self::url('users', '/admin/users', 'user', 'admin/users'),
                self::url('search', '/admin/search', 'search', 'admin/search'),
                self::link('notifications', 'admin.notifications.index', 'bell', 'admin.notifications.*', 'notifications'),
                // SEO живёт в отдельном модуле с собственным префиксом маршрутов,
                // поэтому активным раздел считается и по имени маршрута, и по пути
                (self::link('seo', 'seo.pages.index', 'search', 'seo.*')
                    ?? self::url('seo', '/admin/seo/pages', 'search', 'admin/seo*'))
                    + ['also' => 'admin/seo*'],
                self::link('themes', 'admin.visual.themes.index', 'palette', 'admin.visual.themes.*'),
                self::link('fragments', 'admin.visual.fragments.index', 'puzzle', 'admin.visual.fragments.*'),
                self::link('localization', 'admin.localization.index', 'globe', 'admin.localization.*'),
                self::link('captcha', 'admin.captcha.index', 'shield', 'admin.captcha.*'),
                self::url('accessibility', '/admin/accessibility', 'user', 'admin/accessibility*'),
            ],

            __('admin.section_groups.payments') => [
                self::link('payments', 'admin.payments.index', 'credit-card', 'admin.payments.*'),
                self::link('orders', 'admin.orders.index', 'shopping-cart', 'admin.orders.*', 'orders'),
                self::link('delivery', 'admin.delivery.index', 'truck', 'admin.delivery.*'),
            ],
        ];

        // Пункты отключённых модулей выпадают как null
        return array_map(fn (array $links) => array_values(array_filter($links)), $groups);
    }

    /**
     * Дашборд — отдельный пункт вне групп.
     *
     * Раньше попасть на него можно было только через логотип в шапке сайдбара,
     * и это читалось как украшение, а не как ссылка: у логотипа нет ни подписи
     * «где я сейчас», ни подсветки активного раздела. Теперь это обычный пункт
     * навигации со всеми признаками остальных — а логотип по-прежнему ведёт
     * туда же, потому что так принято и никому не мешает.
     */
    public static function dashboard(): ?array
    {
        return self::link('dashboard', 'admin.dashboard', 'dashboard', 'admin.dashboard');
    }

    /**
     * Плоский список всех доступных разделов — для поиска.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $flat = [];

        if ($dashboard = self::dashboard()) {
            $flat[] = $dashboard + ['group' => __('admin.section_groups.panel')];
        }

        foreach (self::groups() as $group => $links) {
            foreach ($links as $link) {
                $flat[] = $link + ['group' => $group];
            }
        }

        return $flat;
    }

    /**
     * Разделы, подходящие под строку поиска: по названию или по синониму.
     *
     * Название ГРУППЫ в поиске намеренно не участвует: по запросу «тем»
     * группа «Система» вытаскивала половину разделов сразу (Сис-ТЕМ-а),
     * занимала весь лимит и выталкивала настоящие «Темы» из выдачи.
     *
     * Порядок: сначала разделы, чьё название начинается с запроса, затем
     * остальные совпадения по названию, и лишь потом найденные по синониму —
     * иначе точный раздел тонет среди похожих.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, int $limit = 5): array
    {
        $query = trim(mb_strtolower($query));

        if ($query === '') {
            return [];
        }

        $byRank = [[], [], []];

        foreach (self::all() as $section) {
            $label = mb_strtolower($section['label']);

            if (str_starts_with($label, $query)) {
                $byRank[0][] = $section;
                continue;
            }

            if (str_contains($label, $query)) {
                $byRank[1][] = $section;
                continue;
            }

            foreach ($section['keywords'] as $keyword) {
                if (str_contains(mb_strtolower($keyword), $query)) {
                    $byRank[2][] = $section;
                    break;
                }
            }
        }

        return array_slice(array_merge(...$byRank), 0, $limit);
    }

    /**
     * Пункт по имени маршрута. Возвращает null, если маршрута нет
     * (модуль отключён) — вызывающий код такие пункты отбрасывает.
     *
     * $key — ключ словаря admin.php (в каталоге каждой локали), а не готовая
     * подпись: по нему берутся и название раздела, и синонимы для поиска.
     * Раньше здесь были русские литералы, и панель оставалась русской при
     * любом выбранном языке.
     */
    private static function link(
        string $key,
        string $route,
        string $icon,
        string $pattern,
        ?string $counter = null,
    ): ?array {
        if (! Route::has($route)) {
            return null;
        }

        return [
            'key'      => $key,
            'label'    => __('admin.sections.' . $key),
            'url'      => route($route),
            'icon'     => $icon,
            'pattern'  => $pattern,
            'is_route' => true,
            'keywords' => self::keywords($key),
            // Ключ из App\Support\AdminCounters — у раздела рядом с названием
            // покажется число «есть новое». null — счётчика нет.
            'counter'  => $counter,
        ];
    }

    /**
     * Пункт по прямому пути — там, где именованного маршрута нет.
     * Путь остаётся относительным (как было в разметке сайдбара): абсолютный
     * URL прибил бы схему и хост, что мешает за прокси и в тестах.
     */
    private static function url(string $key, string $path, string $icon, string $pattern): array
    {
        return [
            'key'      => $key,
            'label'    => __('admin.sections.' . $key),
            'url'      => $path,
            'icon'     => $icon,
            'pattern'  => $pattern,
            'is_route' => false,
            'keywords' => self::keywords($key),
            'counter'  => null,
        ];
    }

    /**
     * Синонимы раздела для поиска. В словаре лежат строкой через запятую —
     * так их проще держать в семи локалях и сверять командой lang:check,
     * которая сравнивает ключи, а не длину вложенных массивов.
     */
    private static function keywords(string $key): array
    {
        $line = __('admin.section_keywords.' . $key);

        // Ключа нет — переводчик вернёт саму строку ключа
        if ($line === 'admin.section_keywords.' . $key) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $line))));
    }
}
