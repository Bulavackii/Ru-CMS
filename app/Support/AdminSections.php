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
     * сайдбар. Пункт исчезает, если модуль выключен в разделе «Модули» или
     * если его маршрута нет вовсе.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function groups(): array
    {
        $groups = [
            __('admin.section_groups.content') => [
                self::link('menus', 'admin.menus.index', 'dashboard', 'admin.menus.*', module: 'Menu'),
                self::link('news', 'admin.news.index', 'file-text', 'admin.news.*', module: 'News'),
                self::link('pages', 'admin.pages.index', 'file-text', 'admin.pages.*', module: 'Menu'),
                self::link('categories', 'admin.categories.index', 'folder', 'admin.categories.*', module: 'Categories'),
                self::link('slideshow', 'admin.slideshow.index', 'image', 'admin.slideshow.*', module: 'Slideshow'),
                self::link('files', 'admin.files.index', 'folder', 'admin.files.*', module: 'Files'),
                self::link('newsio', 'admin.newsio.index', 'arrow-up', 'admin.newsio.*', module: 'NewsIO'),
            ],

            __('admin.section_groups.system') => [
                // Все пункты идут через link(): он один умеет отсеять и
                // отсутствующий маршрут, и выключенный модуль. Раньше часть
                // разделов задавалась прямым путём — такой пункт не исчезал
                // никогда, каким бы ни был флаг модуля.
                self::link('modules', 'admin.modules.index', 'puzzle', 'admin/modules'),
                self::link('users', 'admin.users.index', 'user', 'admin.users.*', module: 'Users'),
                self::link('search', 'admin.search.index', 'search', 'admin/search', module: 'Search'),
                self::link('notifications', 'admin.notifications.index', 'bell', 'admin.notifications.*', 'notifications', module: 'Notifications'),
                // SEO живёт в отдельном модуле с собственным префиксом маршрутов,
                // поэтому активным раздел считается и по имени маршрута, и по пути
                self::link('seo', 'seo.pages.index', 'search', 'seo.*', also: 'admin/seo*', module: 'Seo'),
                self::link('themes', 'admin.visual.themes.index', 'palette', 'admin.visual.themes.*', module: 'Visual'),
                self::link('fragments', 'admin.visual.fragments.index', 'puzzle', 'admin.visual.fragments.*', module: 'Visual'),
                self::link('localization', 'admin.localization.index', 'globe', 'admin.localization.*', module: 'Localization'),
                self::link('captcha', 'admin.captcha.index', 'shield', 'admin.captcha.*', module: 'Captcha'),
                self::link('accessibility', 'admin.accessibility.index', 'user', 'admin/accessibility*', module: 'Accessibility'),
            ],

            __('admin.section_groups.payments') => [
                self::link('payments', 'admin.payments.index', 'credit-card', 'admin.payments.*', module: 'Payments'),
                self::link('orders', 'admin.orders.index', 'shopping-cart', 'admin.orders.*', 'orders', module: 'Payments'),
                self::link('delivery', 'admin.delivery.index', 'truck', 'admin.delivery.*', module: 'Delivery'),
            ],
        ];

        // Пункты отсутствующих маршрутов и выключенных модулей выпадают как null
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
        // Звёздочка в образце: у главной панели два адреса — канонический
        // /admin/dashboard и прежний /admin. Без неё логотип не подсвечивался
        // бы как текущий раздел при заходе по старому адресу.
        return self::link('dashboard', 'admin.dashboard', 'dashboard', 'admin.dashboard*');
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
     * Пункт по имени маршрута. Возвращает null, если пункт показывать не надо —
     * вызывающий код такие отбрасывает.
     *
     * Не надо в двух случаях: маршрута нет вовсе или модуль выключен в разделе
     * «Модули». Второе проверяется отдельно и намеренно: маршруты выключенного
     * модуля из роутера НЕ убираются (на них ссылается корзина в шапке сайта,
     * ссылки на страницы на главной, быстрые действия панели — снятый маршрут
     * ронял бы всё это пятисоткой), поэтому по Route::has() выключение не
     * видно. Раньше проверки на модуль не было вовсе, и раздел оставался в
     * меню после выключения — с чего эта правка и началась.
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
        ?string $also = null,
        ?string $module = null,
    ): ?array {
        if (! Route::has($route)) {
            return null;
        }

        if ($module !== null && ! module_enabled($module)) {
            return null;
        }

        return ($also ? ['also' => $also] : []) + [
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
