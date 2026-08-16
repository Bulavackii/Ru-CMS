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
            'bootstrap' => [
                'bell' => 'bell', 'shopping-cart' => 'cart', 'message' => 'chat-dots',
                'search' => 'search', 'plus' => 'plus-lg', 'folder' => 'folder',
                'image' => 'image', 'file-text' => 'file-text', 'puzzle' => 'puzzle',
                'home' => 'house', 'user' => 'person', 'logout' => 'box-arrow-right',
                'login' => 'box-arrow-in-right', 'mail' => 'envelope', 'dashboard' => 'grid',
                'globe' => 'globe',
            ],
            'remix' => [
                'bell' => 'notification-3-line', 'shopping-cart' => 'shopping-cart-2-line',
                'message' => 'message-3-line', 'search' => 'search-line', 'plus' => 'add-line',
                'folder' => 'folder-3-line', 'image' => 'image-line', 'file-text' => 'file-text-line',
                'puzzle' => 'puzzle-2-line', 'home' => 'home-2-line', 'user' => 'user-3-line',
                'logout' => 'logout-box-r-line', 'login' => 'login-box-line', 'mail' => 'mail-line',
                'dashboard' => 'dashboard-2-line', 'globe' => 'earth-line',
            ],
            'tabler' => [
                'bell' => 'bell', 'shopping-cart' => 'shopping-cart', 'message' => 'message',
                'search' => 'search', 'plus' => 'plus', 'folder' => 'folder', 'image' => 'photo',
                'file-text' => 'file-text', 'puzzle' => 'puzzle', 'home' => 'home', 'user' => 'user',
                'logout' => 'logout', 'login' => 'login', 'mail' => 'mail', 'dashboard' => 'layout-dashboard',
                'globe' => 'world',
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
            'phosphor' => [
                'alert-circle' => 'warning-circle', 'alert-octagon' => 'warning-octagon', 'alert-triangle'
                => 'warning', 'arrow-up-right-from-square' => 'arrow-square-out', 'at-sign' => 'at', 'badge-
                check' => 'seal-check', 'badge-info' => 'info', 'banknote' => 'money', 'bars' => 'list',
                'cable' => 'plugs', 'chevron-down' => 'caret-down', 'chevron-left' => 'caret-left',
                'chevron-right' => 'caret-right', 'circle-dashed' => 'circle-dashed', 'circle-help' =>
                'question', 'clipboard-check' => 'clipboard-text', 'cog' => 'gear', 'cogs' => 'gear-six',
                'database-zap' => 'database', 'edit' => 'pencil-simple', 'exclamation-triangle' =>
                'warning', 'external-link' => 'arrow-square-out', 'eye-off' => 'eye-slash', 'file-cog' =>
                'file-text', 'github' => 'github-logo', 'grip-vertical' => 'dots-six-vertical', 'hashtag' =>
                'hash', 'help-circle' => 'question', 'home' => 'house', 'key-round' => 'key', 'languages' =>
                'translate', 'layers' => 'stack', 'layout-dashboard' => 'squares-four', 'life-buoy' =>
                'lifebuoy', 'loader-2' => 'spinner', 'log-in' => 'sign-in', 'log-out' => 'sign-out', 'mail'
                => 'envelope-simple', 'map' => 'map-trifold', 'menu' => 'list', 'message' => 'chat-circle',
                'octagon-alert' => 'warning-octagon', 'party-popper' => 'confetti', 'plug-zap' => 'plugs-
                connected', 'power-off' => 'power', 'puzzle' => 'puzzle-piece', 'refresh-cw' => 'arrows-
                clockwise', 'rotate-cw' => 'arrow-clockwise', 'save' => 'floppy-disk', 'scan-search' =>
                'magnifying-glass', 'search' => 'magnifying-glass', 'server' => 'hard-drives', 'settings' =>
                'gear', 'shield-check' => 'shield-check', 'sign-in-alt' => 'sign-in', 'sign-out-alt' =>
                'sign-out', 'sliders-horizontal' => 'sliders-horizontal', 'square-pen' => 'pencil-simple-
                line', 'ticket-percent' => 'ticket', 'times' => 'x', 'trash-2' => 'trash', 'trash-alt' =>
                'trash', 'type' => 'text-aa', 'unlock' => 'lock-open', 'user-round' => 'user', 'vk' =>
                'chat-circle', 'wand-2' => 'magic-wand', 'x' => 'x', 'youtube' => 'youtube-logo', 'zap' =>
                'lightning',
            ],

            'boxicons' => [
                'alert-circle' => 'bx-error-circle', 'alert-octagon' => 'bx-error-circle', 'alert-triangle'
                => 'bx-error', 'arrow-down' => 'bx-down-arrow-alt', 'arrow-left' => 'bx-left-arrow-alt',
                'arrow-right' => 'bx-right-arrow-alt', 'arrow-up' => 'bx-up-arrow-alt', 'arrow-up-right-
                from-square' => 'bx-link-external', 'at-sign' => 'bx-at', 'badge-check' => 'bx-badge-check',
                'badge-info' => 'bx-info-circle', 'banknote' => 'bx-money', 'bars' => 'bx-menu', 'cable' =>
                'bx-plug', 'chevron-down' => 'bx-chevron-down', 'chevron-left' => 'bx-chevron-left',
                'chevron-right' => 'bx-chevron-right', 'circle-dashed' => 'bx-circle', 'circle-help' => 'bx-
                help-circle', 'clipboard-check' => 'bx-clipboard', 'clock' => 'bx-time', 'cog' => 'bx-cog',
                'cogs' => 'bx-cog', 'database' => 'bx-data', 'database-zap' => 'bx-data', 'edit' => 'bx-
                edit', 'exclamation-triangle' => 'bx-error', 'external-link' => 'bx-link-external', 'eye' =>
                'bx-show', 'eye-off' => 'bx-hide', 'file-cog' => 'bx-file', 'file-text' => 'bx-file',
                'gauge' => 'bx-tachometer', 'github' => 'bxl-github', 'grip-vertical' => 'bx-dots-vertical-
                rounded', 'hard-drive' => 'bx-server', 'hashtag' => 'bx-hash', 'help-circle' => 'bx-help-
                circle', 'home' => 'bx-home', 'house' => 'bx-home', 'info' => 'bx-info-circle', 'key-round'
                => 'bx-key', 'keyboard' => 'bxs-keyboard', 'languages' => 'bx-globe', 'layers' => 'bx-
                layer', 'layout-dashboard' => 'bx-grid-alt', 'life-buoy' => 'bx-buoy', 'lightbulb' => 'bx-
                bulb', 'list' => 'bx-list-ul', 'list-checks' => 'bx-list-check', 'loader-2' => 'bx-loader',
                'log-in' => 'bx-log-in', 'log-out' => 'bx-log-out', 'mail' => 'bx-envelope', 'map' => 'bx-
                map', 'menu' => 'bx-menu', 'message' => 'bx-message', 'octagon-alert' => 'bx-error-circle',
                'party-popper' => 'bx-party', 'plug-zap' => 'bx-plug', 'power-off' => 'bx-power-off',
                'puzzle' => 'bx-extension', 'puzzle-piece' => 'bx-extension', 'refresh-cw' => 'bx-refresh',
                'rotate-cw' => 'bx-refresh', 'save' => 'bx-save', 'scan-search' => 'bx-search-alt', 'search'
                => 'bx-search', 'server' => 'bx-server', 'settings' => 'bx-cog', 'shield-check' => 'bx-
                shield', 'shopping-cart' => 'bx-cart', 'sign-in-alt' => 'bx-log-in', 'sign-out-alt' => 'bx-
                log-out', 'skip-forward' => 'bx-skip-next', 'sliders-horizontal' => 'bx-slider', 'square-
                pen' => 'bx-edit-alt', 'ticket' => 'bx-purchase-tag', 'ticket-percent' => 'bx-purchase-tag',
                'times' => 'bx-x', 'trash' => 'bx-trash', 'trash-2' => 'bx-trash', 'trash-alt' => 'bx-
                trash', 'type' => 'bx-text', 'unlock' => 'bx-lock-open', 'user-round' => 'bx-user', 'vk' =>
                'bxl-vk', 'wand-2' => 'bxs-magic-wand', 'x' => 'bx-x', 'youtube' => 'bxl-youtube', 'zap' =>
                'bx-bolt-circle',
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
