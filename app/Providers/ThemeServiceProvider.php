<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Modules\Visual\Models\Theme;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Кэширование активной темы
        $this->registerThemeComposer();

        // Регистрация директивы иконок
        $this->registerIconDirective();

        // Глобальные view данные
        $this->shareGlobalData();
    }

    private function registerThemeComposer(): void
    {
        View::composer('*', function ($view) {
            $theme = $this->getCachedTheme();
            $view->with('activeTheme', $theme);
            $view->with('__activeTheme', $theme);
        });

        // 🎚️ Тема, выбранная посетителем, и список тем для переключателя в
        // шапке сайта. Считается на сервере: токены попадают в #theme-vars
        // сразу, без мигания и расхождения с классами fx-themed/fx-theme-dark.
        // Выбор личный (сессия) и активную тему сайта не меняет.
        View::composer(['layouts.frontend', 'layouts.partials.header'], function ($view) {
            $slug = null; // личного выбора нет: тема сайта задаётся в панели

            try {
                if (!$this->isInstalled() || !Schema::hasTable('visual_themes')) {
                    throw new \RuntimeException('themes table is not available');
                }

                $view->with([
                    'siteTheme'     => Theme::resolveForVisitor($slug),
                    'themeOptions'  => Theme::publicList(),
                    'siteThemeSlug' => $slug,
                ]);
            } catch (\Throwable $e) {
                // Сайт не должен падать из-за тем: нет таблицы или БД —
                // работает прежнее оформление из лейаута
                Log::debug('Theme switcher skipped: ' . $e->getMessage());
                $view->with(['siteTheme' => null, 'themeOptions' => collect(), 'siteThemeSlug' => null]);
            }
        });

        // 🎛️ То же самое для ПАНЕЛИ. Личного выбора в сессии больше нет:
        // оформление одно на сайт и на панель, хранится в базе и потому
        // переживает перезаход, оставаясь общим для всех посетителей.
        View::composer(['layouts.admin', 'layouts.admin.header'], function ($view) {
            $slug = null; // панель показывает ту же тему, что и сайт

            try {
                if (!$this->isInstalled() || !Schema::hasTable('visual_themes')) {
                    throw new \RuntimeException('themes table is not available');
                }

                $view->with([
                    'panelTheme'     => Theme::resolveForVisitor($slug),
                    'panelThemes'    => Theme::publicList(),
                    'panelThemeSlug' => $slug,
                ]);
            } catch (\Throwable $e) {
                // Панель не должна падать из-за тем: без таблицы просто
                // остаётся акцент активной темы (или дефолтный индиго)
                Log::debug('Admin theme switcher skipped: ' . $e->getMessage());
                $view->with(['panelTheme' => null, 'panelThemes' => collect(), 'panelThemeSlug' => null]);
            }
        });
    }

    private function getCachedTheme(): ?Theme
    {
        if (!$this->isInstalled()) {
            return null;
        }

        // Разрешение активной темы живёт в модели — здесь была вторая копия
        // той же логики со своим кешем и своим ключом active_theme_id.
        // Две копии успели разойтись: эта записывала ключ через forever, а
        // getActive() читала его ПЕРЕД базой, и применённая тема переставала
        // доходить до экрана.
        try {
            if (!class_exists(Theme::class) || !Schema::hasTable('visual_themes')) {
                return null;
            }

            return Theme::getActive();
        } catch (\Throwable $e) {
            Log::error('Theme loading failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function registerIconDirective(): void
    {
        Blade::directive('themeIcon', function ($expression = null) {
            $expr = trim((string)$expression);
            if ($expr === '' || $expr === '()') {
                return "<?php echo \\App\\Providers\\ThemeServiceProvider::renderThemeIcon(); ?>";
            }
            return "<?php echo \\App\\Providers\\ThemeServiceProvider::renderThemeIcon($expression); ?>";
        });
    }

    private function shareGlobalData(): void
    {
        // Уведомления
        $notifications = collect();
        $accessibility = null;

        if ($this->isInstalled()) {
            try {
                if (class_exists(\Modules\Notifications\Models\Notification::class)) {
                    $notifications = \Modules\Notifications\Models\Notification::where('enabled', true)->get();
                }
            } catch (\Throwable $e) {
            }

            try {
                if (
                    class_exists(\Modules\Accessibility\Models\AccessibilitySetting::class)
                    && Schema::hasTable('accessibility_settings')
                ) {
                    $accessibility = \Modules\Accessibility\Models\AccessibilitySetting::settings();
                }
            } catch (\Throwable $e) {
            }
        }

        View::share('notifications', $notifications);
        View::share('accessibility', $accessibility);

        // Компоненты
        if (class_exists(\Modules\Notifications\View\Components\Frontend\NotificationsComponent::class)) {
            Blade::component('frontend-notifications', \Modules\Notifications\View\Components\Frontend\NotificationsComponent::class);
        }

        if (class_exists(\Modules\Accessibility\Views\Components\AccessibilityWidget::class)) {
            Blade::component('accessibility-widget', \Modules\Accessibility\Views\Components\AccessibilityWidget::class);
        }

        if (class_exists(\Modules\Localization\Views\Components\CountrySwitcher::class)) {
            Blade::component('country-switcher', \Modules\Localization\Views\Components\CountrySwitcher::class);
        }
    }

    private function isInstalled(): bool
    {
        return file_exists(install_lock_path());
    }

    /**
     * Рендер иконки (статический метод для Blade directive)
     */
    public static function renderThemeIcon($name = 'circle-question', $class = '')
    {
        $name = $name ? trim($name, " \t\n\r\0\x0B'\"") : 'circle-question';
        $class = trim((string)$class, " \t\n\r\0\x0B'\"");

        try {
            $theme = self::getTheme();
            $cfg = $theme?->config ?? [];
            $mode = data_get($cfg, 'icon_mode', 'lucide');

            return self::renderIcon($name, $class, $mode, $cfg);
        } catch (\Throwable $e) {
            return '<i class="fa-solid fa-circle-question ' . e($class) . '"></i>';
        }
    }

    private static function getTheme(): ?Theme
    {
        static $theme = null;

        if ($theme !== null) {
            return $theme;
        }

        if (!file_exists(install_lock_path())) {
            return null;
        }

        try {
            // Третья копия того же разрешения жила здесь. Источник один —
            // модель, иначе значки могли рисоваться набором ОДНОЙ темы, а
            // цвета браться из ДРУГОЙ.
            $theme = Theme::getActive();
        } catch (\Throwable $e) {
            $theme = null;
        }

        return $theme;
    }

    private static function renderIcon(string $name, string $class, string $mode, array $cfg): string
    {
        $aliases = self::getAliases();
        $pools = self::getPools();

        // 🔴 Tabler отдаём ФАЙЛАМИ, а не шрифтом — даже если в теме записан
        // старый режим `tabler`.
        //
        // Шрифтовая сборка Tabler рисуется сплошными силуэтами: доля заливки
        // при показе в 16 пикселях 0.81 против 0.51 у Bootstrap, середина
        // закрашена у всех значков подряд — `ti-circle` выходит диском, а не
        // кольцом. Проверено в полной изоляции, со своим объявлением шрифта и
        // без стилей проекта. Файл при этом ОФИЦИАЛЬНЫЙ: побайтово совпал со
        // скачанным с unpkg (3.36.0), и коды в CSS сошлись с официальными до
        // единого — все 6040 имён. Те же значки в виде SVG рисуются верно.
        //
        // Из списка в панели режим убран, но у существующих тем он мог
        // остаться записанным — тогда без этой ветки они молча рисовали бы
        // пятна. Каталога нет (набор не выкачали) — работает как раньше.
        if ($mode === 'tabler' && is_dir(public_path('assets/icons/tabler'))) {
            $mode = 'svg';
            $cfg['icons_path'] = '/assets/icons/tabler';
        }

        // SVG mode
        if ($mode === 'svg') {
            $result = self::renderSvgIcon($name, $class, $cfg, $pools);
            if ($result !== null) {
                return $result;
            }
        }

        // Webfont mode
        $modeKey = in_array($mode, ['bootstrap', 'remix', 'tabler', 'lucide', 'phosphor', 'boxicons'], true) ? $mode : null;
        if ($modeKey && isset($aliases[$modeKey][strtolower($name)])) {
            $name = $aliases[$modeKey][strtolower($name)];
        }

        return self::renderWebfontIcon($name, $class, $mode, $pools);
    }

    private static function renderSvgIcon(string $name, string $class, array $cfg, array $pools): ?string
    {
        $iconsUrl = data_get($cfg, 'icons_path');
        if (!$iconsUrl) {
            return null;
        }

        $rel = ltrim(parse_url($iconsUrl, PHP_URL_PATH) ?: '', '/');
        $dir = public_path($rel);

        if (!is_dir($dir)) {
            return null;
        }

        if ($name === 'random') {
            $files = glob($dir . '/*.svg') ?: [];
            if ($files) {
                $file = $files[array_rand($files)];
                return self::injectSvgClass($file, $class);
            }
        } else {
            $file = $dir . '/' . basename($name) . '.svg';
            if (is_file($file)) {
                return self::injectSvgClass($file, $class);
            }
            // Fallback to random
            return self::renderSvgIcon('random', $class, $cfg, $pools);
        }

        return null;
    }

    private static function injectSvgClass(string $file, string $class): string
    {
        $svg = @file_get_contents($file) ?: '';
        if (!$svg) {
            return '';
        }

        $svg = preg_replace('/<svg\b([^>]*)class="([^"]*)"/i', '<svg$1class="$2 ' . e($class) . '"', $svg, 1, $count);
        if (!$count) {
            $svg = preg_replace('/<svg\b([^>]*)>/', '<svg$1 class="' . e($class) . '">', $svg, 1);
        }

        return $svg;
    }

    private static function renderWebfontIcon(string $name, string $class, string $mode, array $pools): string
    {
        $pick = function (string $set) use ($pools) {
            $arr = $pools[$set] ?? [];
            return $arr ? $arr[array_rand($arr)] : null;
        };

        if ($mode === 'lucide') {
            $icon = $name === 'random' ? ($pick('lucide') ?? 'circle-help') : $name;
            return '<i data-lucide="' . e($icon) . '" class="' . e($class) . '"></i>';
        }

        if ($mode === 'bootstrap') {
            $icon = $name === 'random' ? ($pick('bootstrap') ?? 'question-circle') : $name;
            return '<i class="bi bi-' . e($icon) . ' ' . e($class) . '"></i>';
        }

        if ($mode === 'remix') {
            $icon = $name === 'random' ? ($pick('remix') ?? 'question-line') : $name;
            return '<i class="ri-' . e($icon) . ' ' . e($class) . '"></i>';
        }

        if ($mode === 'tabler') {
            $icon = $name === 'random' ? ($pick('tabler') ?? 'help-circle') : $name;
            return '<i class="ti ti-' . e($icon) . ' ' . e($class) . '"></i>';
        }

        if ($mode === 'phosphor') {
            $icon = $name === 'random' ? ($pick('phosphor') ?? 'question') : $name;
            return '<i class="ph ph-' . e($icon) . ' ' . e($class) . '"></i>';
        }

        if ($mode === 'boxicons') {
            $icon = $name === 'random' ? ($pick('boxicons') ?? 'bx-help-circle') : $name;

            // Имена этого набора несут приставку сами: bx- обычные, bxs-
            // заливкой, bxl- логотипы. Пришло имя без приставки — значит, оно
            // не из этого набора, дописываем обычную.
            if (!preg_match('~^bx[sl]?-~', $icon)) {
                $icon = 'bx-' . $icon;
            }

            return '<i class="bx ' . e($icon) . ' ' . e($class) . '"></i>';
        }

        // Font Awesome fallback
        $faMap = self::getFaMap();
        if ($name === 'random') {
            $pool = array_values($faMap);
            $fa = $pool[array_rand($pool)];
            return '<i class="fa-solid fa-' . e($fa) . ' ' . e($class) . '"></i>';
        }
        $fa = $faMap[$name] ?? $name;
        return '<i class="fa-solid fa-' . e($fa) . ' ' . e($class) . '"></i>';
    }

    /**
     * Карта соответствий имён значков наборам.
     *
     * ⚠️ Публичная не «на всякий случай»: дерево пунктов меню рисует
     * JAVASCRIPT и берёт имя значка прямо из базы. Мимо этой карты — значит
     * мимо всех переводов: на странице правки меню в консоль сыпалось
     * «icon name was not found» по разу на каждый пункт с названием сети.
     * Отдать карту в браузер — единственный способ не заводить её вторую
     * копию в скрипте.
     */
    public static function aliasesFor(string $набор): array
    {
        return self::getAliases()[$набор] ?? [];
    }

    private static function getAliases(): array
    {
        return [
            // ⚠️ Каждое имя СВЕРЕНО с самим набором
            // (public/assets/css/bootstrap-icons.css): промах молчаливый —
            // имя уходит в набор как есть, значок не рисуется, и видно это
            // только на той странице, куда случайно зайдёшь.
            'bootstrap' => [
                'alert-triangle' => 'exclamation-triangle', 'arrow-left' => 'arrow-left', 'arrow-up' => 'arrow-up',
                'arrow-up-right-from-square' => 'box-arrow-up-right', 'bars' => 'list', 'bell' => 'bell',
                'box' => 'box', 'bug' => 'bug', 'calendar' => 'calendar', 'check' => 'check-lg',
                'chevron-down' => 'chevron-down', 'chevron-left' => 'chevron-left', 'chevron-right' => 'chevron-right',
                'code' => 'code-slash', 'cogs' => 'gear', 'credit-card' => 'credit-card', 'dashboard' => 'grid',
                'edit' => 'pencil', 'exclamation-triangle' => 'exclamation-triangle', 'eye' => 'eye',
                'file-text' => 'file-text', 'files' => 'files', 'folder' => 'folder', 'github' => 'github',
                'globe' => 'globe', 'grip-vertical' => 'grip-vertical', 'heart' => 'heart', 'help-circle' => 'question-circle',
                'home' => 'house', 'image' => 'image', 'info' => 'info-circle', 'keyboard' => 'keyboard',
                'lightbulb' => 'lightbulb', 'link' => 'link-45deg', 'list' => 'list', 'lock' => 'lock',
                'login' => 'box-arrow-in-right', 'logout' => 'box-arrow-right', 'mail' => 'envelope',
                'map' => 'map', 'map-pin' => 'geo-alt', 'message' => 'chat-dots', 'newspaper' => 'newspaper',
                'palette' => 'palette', 'phone' => 'telephone', 'plus' => 'plus-lg', 'puzzle' => 'puzzle',
                'save' => 'save', 'search' => 'search', 'send' => 'send', 'settings' => 'gear',
                'share-nodes' => 'share', 'shield' => 'shield', 'shopping-cart' => 'cart', 'sign-in-alt' => 'box-arrow-in-right',
                'sign-out-alt' => 'box-arrow-right', 'star' => 'star', 'tag' => 'tag', 'times' => 'x-lg',
                'trash' => 'trash', 'trash-alt' => 'trash', 'truck' => 'truck', 'unlock' => 'unlock',
                'user' => 'person', 'user-plus' => 'person-plus', 'users' => 'people', 'youtube' => 'youtube',
                // Брендовых глифов VK, MAX и Rutube в наборе нет — подменяем
                // по СМЫСЛУ (сеть общения — облако разговора, видеосервис —
                // значок воспроизведения), как это уже сделано в Lucide.
                'vk' => 'chat-dots', 'max' => 'chat-square-dots', 'rutube' => 'play-btn',
            ],
            'remix' => [
                'alert-triangle' => 'error-warning-line', 'arrow-left' => 'arrow-left-line', 'arrow-up' => 'arrow-up-line',
                'arrow-up-right-from-square' => 'external-link-line', 'bars' => 'menu-line', 'bell' => 'notification-3-line',
                'box' => 'archive-2-line', 'bug' => 'bug-line', 'calendar' => 'calendar-line', 'check' => 'check-line',
                'chevron-down' => 'arrow-down-s-line', 'chevron-left' => 'arrow-left-s-line', 'chevron-right' => 'arrow-right-s-line',
                'code' => 'code-s-slash-line', 'cogs' => 'settings-3-line', 'credit-card' => 'bank-card-line',
                'clock' => 'time-line',
                'dashboard' => 'dashboard-2-line', 'edit' => 'edit-line', 'exclamation-triangle' => 'error-warning-line',
                'eye' => 'eye-line', 'file-text' => 'file-text-line', 'files' => 'file-copy-line',
                'folder' => 'folder-3-line', 'github' => 'github-line', 'globe' => 'earth-line',
                'grip-vertical' => 'draggable', 'heart' => 'heart-line', 'help-circle' => 'question-line',
                'home' => 'home-2-line', 'image' => 'image-line', 'info' => 'information-line',
                'keyboard' => 'keyboard-line', 'lightbulb' => 'lightbulb-line', 'link' => 'links-line',
                'list' => 'menu-line', 'lock' => 'lock-line', 'login' => 'login-box-line', 'logout' => 'logout-box-r-line',
                'mail' => 'mail-line', 'map' => 'map-2-line', 'map-pin' => 'map-pin-line', 'message' => 'message-3-line',
                'newspaper' => 'newspaper-line', 'palette' => 'palette-line', 'phone' => 'phone-line',
                'plus' => 'add-line', 'puzzle' => 'puzzle-2-line', 'save' => 'save-line', 'search' => 'search-line',
                'send' => 'send-plane-line', 'settings' => 'settings-3-line', 'share-nodes' => 'share-line',
                'shield' => 'shield-line', 'shopping-cart' => 'shopping-cart-2-line', 'sign-in-alt' => 'login-box-line',
                'sign-out-alt' => 'logout-box-r-line', 'star' => 'star-line', 'tag' => 'price-tag-3-line',
                'times' => 'close-line', 'trash' => 'delete-bin-line', 'trash-alt' => 'delete-bin-line',
                'truck' => 'truck-line', 'unlock' => 'lock-unlock-line', 'user' => 'user-3-line',
                'user-plus' => 'user-add-line', 'users' => 'team-line', 'youtube' => 'youtube-line',
                // См. пояснение у bootstrap: брендовых глифов нет.
                'vk' => 'message-3-line', 'max' => 'chat-3-line', 'rutube' => 'play-circle-line',
            ],
            // 🔴 TABLER НЕ НАЗНАЧАЕТСЯ ТЕМАМ: в проекте лежит ЗАЛИТЫЙ вариант
            // шрифта вместо контурного.
            //
            // Внешне это выглядело как «значки не дорисовались»: на теме «Алый»
            // в шапке и подвале стояли глухие кружки и квадраты. Разобрано
            // замером — глиф рисуется на холсте, и проверяется его СЕРЕДИНА:
            // у `ti-circle` она закрашена, у `bi-circle` и `ri-...-line` пуста.
            // Точек чернил у Tabler вдвое больше, чем у соседей, и одинаково
            // много у всех значков подряд — верный признак силуэтов.
            //
            // Карта оставлена: подменят файл шрифта на контурный — набор
            // заработает без единой правки. Проверять так:
            //   grep -c 'filled' public/assets/css/tabler-icons.min.css
            // и обязательно замером середины глифа, потому что по имени файла
            // отличить залитый набор от контурного нельзя.
            'tabler' => [
                'alert-triangle' => 'alert-triangle', 'arrow-left' => 'arrow-left', 'arrow-up' => 'arrow-up',
                'arrow-up-right-from-square' => 'external-link', 'bars' => 'menu-2', 'bell' => 'bell',
                'box' => 'box', 'bug' => 'bug', 'calendar' => 'calendar', 'check' => 'check', 'chevron-down' => 'chevron-down',
                'chevron-left' => 'chevron-left', 'chevron-right' => 'chevron-right', 'code' => 'code',
                'cogs' => 'settings', 'credit-card' => 'credit-card', 'dashboard' => 'layout-dashboard',
                'edit' => 'edit', 'exclamation-triangle' => 'alert-triangle', 'eye' => 'eye', 'file-text' => 'file-text',
                'files' => 'files', 'folder' => 'folder', 'github' => 'brand-github', 'globe' => 'world',
                'grip-vertical' => 'grip-vertical', 'heart' => 'heart', 'help-circle' => 'help-circle',
                'home' => 'home', 'image' => 'photo', 'info' => 'info-circle', 'keyboard' => 'keyboard',
                'lightbulb' => 'bulb', 'link' => 'link', 'list' => 'list', 'lock' => 'lock', 'login' => 'login',
                'logout' => 'logout', 'mail' => 'mail', 'map' => 'map', 'map-pin' => 'map-pin',
                'message' => 'message', 'newspaper' => 'news', 'palette' => 'palette', 'phone' => 'phone',
                'plus' => 'plus', 'puzzle' => 'puzzle', 'save' => 'device-floppy', 'search' => 'search',
                'send' => 'send', 'settings' => 'settings', 'share-nodes' => 'share', 'shield' => 'shield',
                'shopping-cart' => 'shopping-cart', 'sign-in-alt' => 'login', 'sign-out-alt' => 'logout',
                'star' => 'star', 'tag' => 'tag', 'times' => 'x', 'trash' => 'trash', 'trash-alt' => 'trash',
                'truck' => 'truck', 'unlock' => 'lock-open', 'user' => 'user', 'user-plus' => 'user-plus',
                'users' => 'users', 'vk' => 'brand-vk', 'youtube' => 'brand-youtube',
                // У Tabler brand-vk НАСТОЯЩИЙ (см. выше), а MAX и Rutube
                // подменяются по смыслу — их глифов нет нигде.
                'max' => 'message-2', 'rutube' => 'player-play',
            ],
            'lucide' => [
                'bell' => 'bell', 'shopping-cart' => 'shopping-cart', 'message' => 'message-circle',
                'search' => 'search', 'plus' => 'plus', 'folder' => 'folder', 'image' => 'image',
                'file-text' => 'file-text', 'puzzle' => 'puzzle', 'home' => 'home', 'user' => 'user',
                'logout' => 'log-out', 'login' => 'log-in', 'mail' => 'mail', 'dashboard' => 'layout-dashboard',
                'globe' => 'globe',

                // Имена из Font Awesome, встречающиеся в шаблонах. В режиме
                // Lucide они уходили в набор как есть, там таких нет — и на
                // месте значка не рисовалось НИЧЕГО, молча: ни ошибки, ни
                // пустого места в разметке. Сверено по самому набору.
                // Названия сетей. Брендовых глифов в этой сборке Lucide НЕТ
                // вовсе (проверено по самому набору: ни vk, ни max, ни
                // rutube, ни telegram), и на месте значка ничего не
                // рисовалось, а в консоли на каждую страницу с меню сыпалось
                // «icon name was not found». Владелец прислал снимок с девятью
                // такими предупреждениями подряд.
                //
                // Подменяем существующими по СМЫСЛУ: сеть общения — облако
                // разговора, видеосервис — значок воспроизведения. Точный
                // фирменный знак здесь и не нужен: это список пунктов меню в
                // панели, а на самом сайте у подвала свои SVG-глифы (см.
                // CLAUDE.md — для MAX и Rutube их пришлось рисовать, потому
                // что в открытых наборах их нет).
                'vk' => 'message-circle',
                'max' => 'message-square',
                'telegram' => 'send',
                'whatsapp' => 'message-circle',
                'rutube' => 'play-circle',
                'youtube' => 'play-circle',
                'github' => 'github',

                'bars' => 'menu',
                'times' => 'x',
                'cogs' => 'settings',
                'hashtag' => 'hash',
                'trash-alt' => 'trash-2',
                'sign-in-alt' => 'log-in',
                'sign-out-alt' => 'log-out',
                'exclamation-triangle' => 'alert-triangle',
                'octagon-alert' => 'alert-octagon',

                // Ещё четыре имени, найденные СПЛОШНОЙ проверкой всех
                // отрисованных страниц панели против самого набора значков.
                // Поштучно такое не ловится: значок просто не рисуется, а
                // предупреждение видно лишь в консоли той страницы, куда
                // случайно зайдёшь.
                // Имена, которые владелец может ввести В ПОЛЕ ЗНАЧКА пункта
                // меню: он берёт их из Font Awesome, а набор у панели другой.
                'file-alt' => 'file-text',
                'address-book' => 'contact',
                'donate' => 'heart',
                'envelope' => 'mail',
                'house' => 'home',

                'circle-question' => 'help-circle',
                'share-nodes' => 'share-2',
                'window-maximize' => 'maximize-2',
                'window-minimize' => 'minimize-2',

            ],

            /*
             * Имена значков в шаблонах свои, а у каждого набора — свои. Без
             * перевода выбранный набор отрисовал бы пустоту на месте больше
             * чем половины значков: совпадает меньше половины имён.
             *
             * Каждый перевод сверен по самой сборке скриптом — несуществующих
             * имён здесь нет.
             */
            // То же самое у Phosphor: без карты не находилось 25 имён из 47.
            'phosphor' => [
                'alert-triangle' => 'warning', 'arrow-left' => 'arrow-left', 'arrow-up' => 'arrow-up',
                'arrow-up-right-from-square' => 'arrow-square-out', 'bars' => 'list', 'bell' => 'bell',
                'box' => 'package', 'bug' => 'bug', 'calendar' => 'calendar', 'check' => 'check',
                'chevron-down' => 'caret-down', 'chevron-left' => 'caret-left', 'chevron-right' => 'caret-right',
                'code' => 'code', 'cogs' => 'gear', 'credit-card' => 'credit-card', 'dashboard' => 'squares-four',
                'edit' => 'pencil-simple', 'exclamation-triangle' => 'warning', 'external-link' => 'arrow-square-out',
                'eye' => 'eye', 'file-text' => 'file-text', 'files' => 'files', 'folder' => 'folder',
                'github' => 'github-logo', 'globe' => 'globe', 'grip-vertical' => 'dots-six-vertical',
                'heart' => 'heart', 'help-circle' => 'question', 'home' => 'house', 'image' => 'image',
                'info' => 'info', 'keyboard' => 'keyboard', 'lightbulb' => 'lightbulb', 'link' => 'link',
                'list' => 'list-bullets', 'lock' => 'lock', 'login' => 'sign-in', 'logout' => 'sign-out',
                'mail' => 'envelope', 'map' => 'map-trifold', 'map-pin' => 'map-pin', 'max' => 'chat-circle-dots',
                'message' => 'chat-circle', 'newspaper' => 'newspaper', 'palette' => 'palette',
                'phone' => 'phone', 'plus' => 'plus', 'puzzle' => 'puzzle-piece', 'rutube' => 'play-circle',
                'save' => 'floppy-disk', 'search' => 'magnifying-glass', 'send' => 'paper-plane-tilt',
                'settings' => 'gear', 'share-nodes' => 'share-network', 'shield' => 'shield', 'shopping-cart' => 'shopping-cart',
                'sign-in-alt' => 'sign-in', 'sign-out-alt' => 'sign-out', 'star' => 'star', 'tag' => 'tag',
                'times' => 'x', 'trash' => 'trash', 'trash-alt' => 'trash', 'truck' => 'truck',
                'unlock' => 'lock-open', 'user' => 'user', 'user-plus' => 'user-plus', 'users' => 'users-three',
                'vk' => 'chat-circle-text', 'youtube' => 'youtube-logo',
            ],

            // 🔴 Карты у Boxicons не было вовсе: имена уходили в набор с одной
            // лишь приставкой `bx-`, и глифа не находилось у 26 имён из 47 —
            // «значки не дорисовываются», как это выглядело у владельца.
            // Каждая цель СВЕРЕНА с самим набором (assets/css/boxicons.css).
            'boxicons' => [
                'alert-triangle' => 'error', 'arrow-left' => 'left-arrow-alt', 'arrow-up' => 'up-arrow-alt',
                'arrow-up-right-from-square' => 'link-external', 'bars' => 'menu', 'bell' => 'bell',
                'box' => 'package', 'bug' => 'bug', 'calendar' => 'calendar', 'check' => 'check',
                'clock' => 'time',
                'chevron-down' => 'chevron-down', 'chevron-left' => 'chevron-left', 'chevron-right' => 'chevron-right',
                'code' => 'code-alt', 'cogs' => 'cog', 'credit-card' => 'credit-card', 'dashboard' => 'grid-alt',
                'edit' => 'edit', 'exclamation-triangle' => 'error', 'external-link' => 'link-external',
                'eye' => 'show', 'file-text' => 'file', 'files' => 'copy', 'folder' => 'folder',
                'github' => 'code-block', 'globe' => 'globe', 'grip-vertical' => 'dots-vertical-rounded',
                'heart' => 'heart', 'help-circle' => 'help-circle', 'home' => 'home', 'image' => 'image',
                'info' => 'info-circle', 'keyboard' => 'terminal', 'lightbulb' => 'bulb', 'link' => 'link',
                'list' => 'list-ul', 'lock' => 'lock', 'login' => 'log-in', 'logout' => 'log-out',
                'mail' => 'envelope', 'map' => 'map', 'map-pin' => 'map-pin', 'max' => 'chat', 'message' => 'message',
                'newspaper' => 'news', 'palette' => 'palette', 'phone' => 'phone', 'plus' => 'plus',
                'puzzle' => 'extension', 'rutube' => 'play-circle', 'save' => 'save', 'search' => 'search',
                'send' => 'send', 'settings' => 'cog', 'share-nodes' => 'share-alt', 'shield' => 'shield',
                'shopping-cart' => 'cart', 'sign-in-alt' => 'log-in', 'sign-out-alt' => 'log-out',
                'star' => 'star', 'tag' => 'purchase-tag', 'times' => 'x', 'trash' => 'trash', 'trash-alt' => 'trash',
                'truck' => 'car', 'unlock' => 'lock-open', 'user' => 'user', 'user-plus' => 'user-plus',
                'users' => 'group', 'vk' => 'message-rounded', 'youtube' => 'play-circle',
            ],
        ];
    }

    private static function getPools(): array
    {
        return [
            'phosphor' => ['house', 'star', 'gear', 'bell', 'user', 'magnifying-glass', 'folder', 'image', 'package', 'tag', 'truck', 'credit-card', 'shopping-cart', 'palette', 'puzzle-piece', 'file-text', 'files', 'newspaper', 'users', 'list', 'chat-circle', 'map-pin', 'bug', 'globe', 'arrow-up', 'caret-right', 'caret-left'],
            'boxicons' => ['bx-home', 'bx-star', 'bx-cog', 'bx-bell', 'bx-user', 'bx-search', 'bx-folder', 'bx-image', 'bx-package', 'bx-purchase-tag', 'bx-car', 'bx-credit-card', 'bx-cart', 'bx-palette', 'bx-extension', 'bx-file', 'bx-copy', 'bx-news', 'bx-group', 'bx-list-ul', 'bx-message', 'bx-map-pin', 'bx-bug', 'bx-globe', 'bx-up-arrow-alt', 'bx-chevron-right', 'bx-chevron-left'],
            'bootstrap' => ['house', 'star', 'gear', 'bell', 'person', 'search', 'folder', 'image', 'box', 'tag', 'truck', 'credit-card', 'cart', 'palette', 'puzzle', 'file-text', 'files', 'newspaper', 'people', 'list', 'chat-dots', 'geo-alt', 'bug', 'globe', 'arrow-up', 'chevron-right', 'chevron-left'],
            'remix' => ['home-2', 'star-line', 'settings-3-line', 'notification-3-line', 'user-3-line', 'search-line', 'folder-3-line', 'image-line', 'archive-2-line', 'price-tag-3-line', 'truck-line', 'bank-card-line', 'shopping-cart-2-line', 'palette-line', 'puzzle-2-line', 'file-text-line', 'file-3-line', 'newspaper-line', 'team-line', 'menu-3-line', 'message-3-line', 'map-pin-line', 'bug-line', 'earth-line', 'arrow-up-line', 'arrow-right-s-line', 'arrow-left-s-line'],
            'tabler' => ['home', 'star', 'settings', 'bell', 'user', 'search', 'folder', 'photo', 'box', 'tag', 'truck', 'credit-card', 'shopping-cart', 'palette', 'puzzle', 'file-text', 'files', 'news', 'users', 'menu-2', 'message', 'map-pin', 'bug', 'world', 'arrow-up', 'chevron-right', 'chevron-left'],
            'lucide' => ['home', 'star', 'settings', 'bell', 'user', 'search', 'folder', 'image', 'box', 'tag', 'truck', 'credit-card', 'shopping-cart', 'palette', 'puzzle', 'file-text', 'files', 'newspaper', 'users', 'menu', 'message-circle', 'map-pin', 'bug', 'globe', 'arrow-up', 'chevron-right', 'chevron-left'],
        ];
    }

    private static function getFaMap(): array
    {
        return [
            // 🔴 Дописано по сверке с самим набором: без этих четырёх имя
            // уходило в Font Awesome как есть, глифа не находилось, и на месте
            // значка не рисовалось ничего. Брендовых знаков Rutube и MAX в
            // наборе нет вовсе — подменяем по смыслу, как в остальных картах.
            'alert-triangle' => 'triangle-exclamation', 'help-circle' => 'circle-question',
            // `info` без соответствия уходил в набор как есть и рисовался ГОЛОЙ
            // буквой «i» — узкой, отчего в меню зазор до слова выходил заметно
            // шире, чем у соседей. Во всех остальных наборах это кружок с «i»,
            // приводим к тому же.
            'info' => 'circle-info',
            'rutube' => 'circle-play', 'max' => 'comment-dots',
            'cart' => 'shopping-cart', 'shopping-cart' => 'shopping-cart', 'user' => 'user',
            'login' => 'sign-in-alt', 'logout' => 'sign-out-alt', 'user-plus' => 'user-plus',
            'cog' => 'cog', 'cogs' => 'cogs', 'phone' => 'phone', 'search' => 'search',
            'home' => 'home', 'book' => 'book', 'question-circle' => 'question-circle',
            'file-text' => 'file-alt', 'handshake' => 'handshake', 'code' => 'code',
            'lightbulb' => 'lightbulb', 'sitemap' => 'sitemap', 'donate' => 'hand-holding-heart',
            'vk' => 'vk', 'telegram-plane' => 'paper-plane', 'whatsapp' => 'whatsapp',
            'github' => 'github', 'youtube' => 'youtube', 'arrow-up' => 'arrow-up',
            'circle-question' => 'circle-question', 'bell' => 'bell', 'message' => 'comment-dots',
            'mail' => 'envelope', 'dashboard' => 'th', 'image' => 'image', 'folder' => 'folder',
            'puzzle' => 'puzzle-piece', 'plus' => 'plus'
        ];
    }
}
