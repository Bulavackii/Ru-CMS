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
            'Контент' => [
                self::link('Меню', 'admin.menus.index', 'dashboard', 'admin.menus.*', ['навигация', 'пункты', 'ссылки']),
                self::link('Новости', 'admin.news.index', 'file-text', 'admin.news.*', ['статьи', 'публикации', 'блог']),
                self::link('Страницы', 'admin.pages.index', 'file-text', 'admin.pages.*', ['текст', 'контент']),
                self::link('Категории', 'admin.categories.index', 'folder', 'admin.categories.*', ['рубрики', 'разделы']),
                self::link('Слайдшоу', 'admin.slideshow.index', 'image', 'admin.slideshow.*', ['слайдер', 'баннер', 'карусель']),
                self::link('Файлы', 'admin.files.index', 'folder', 'admin.files.*', ['медиа', 'картинки', 'изображения', 'загрузки']),
                self::link('Импорт/Экспорт', 'admin.newsio.index', 'arrow-up', 'admin.newsio.*', ['выгрузка', 'загрузка', 'csv']),
            ],

            'Система' => [
                self::url('Модули', '/admin/modules', 'puzzle', 'admin/modules', ['расширения', 'плагины']),
                self::url('Пользователи', '/admin/users', 'user', 'admin/users', ['админы', 'аккаунты', 'роли', 'доступ']),
                self::url('Поиск', '/admin/search', 'search', 'admin/search', ['найти']),
                self::link('Уведомления', 'admin.notifications.index', 'bell', 'admin.notifications.*', ['оповещения', 'push', 'рассылка'], 'notifications'),
                // SEO живёт в отдельном модуле с собственным префиксом маршрутов,
                // поэтому активным раздел считается и по имени маршрута, и по пути
                (self::link('SEO', 'seo.pages.index', 'search', 'seo.*', ['мета', 'описание', 'sitemap', 'продвижение'])
                    ?? self::url('SEO', '/admin/seo/pages', 'search', 'admin/seo*', ['мета', 'описание', 'sitemap', 'продвижение']))
                    + ['also' => 'admin/seo*'],
                self::link('Темы', 'admin.visual.themes.index', 'palette', 'admin.visual.themes.*', ['оформление', 'дизайн', 'цвета', 'шрифт']),
                self::link('Фрагменты', 'admin.visual.fragments.index', 'puzzle', 'admin.visual.fragments.*', ['блоки', 'вставки', 'html']),
                self::link('Локализация', 'admin.localization.index', 'globe', 'admin.localization.*', ['языки', 'переводы', 'страны', 'форматы']),
                self::link('Каптча', 'admin.captcha.index', 'shield', 'admin.captcha.*', ['captcha', 'защита', 'спам', 'боты', 'формы']),
                self::url('Спецвозможности', '/admin/accessibility', 'user', 'admin/accessibility*', ['доступность', 'контраст']),
            ],

            'Оплата' => [
                self::link('Оплата', 'admin.payments.index', 'credit-card', 'admin.payments.*', ['платежи', 'эквайринг', 'касса']),
                self::link('Заказы', 'admin.orders.index', 'shopping-cart', 'admin.orders.*', ['покупки', 'корзина'], 'orders'),
                self::link('Доставка', 'admin.delivery.index', 'truck', 'admin.delivery.*', ['отправка', 'курьер']),
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
        return self::link('Дашборд', 'admin.dashboard', 'dashboard', 'admin.dashboard', ['главная', 'обзор', 'статистика', 'сводка']);
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
            $flat[] = $dashboard + ['group' => 'Панель'];
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
     */
    private static function link(
        string $label,
        string $route,
        string $icon,
        string $pattern,
        array $keywords = [],
        ?string $counter = null,
    ): ?array {
        if (! Route::has($route)) {
            return null;
        }

        return [
            'label'    => $label,
            'url'      => route($route),
            'icon'     => $icon,
            'pattern'  => $pattern,
            'is_route' => true,
            'keywords' => $keywords,
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
    private static function url(string $label, string $path, string $icon, string $pattern, array $keywords = []): array
    {
        return [
            'label'    => $label,
            'url'      => $path,
            'icon'     => $icon,
            'pattern'  => $pattern,
            'is_route' => false,
            'keywords' => $keywords,
            'counter'  => null,
        ];
    }
}
