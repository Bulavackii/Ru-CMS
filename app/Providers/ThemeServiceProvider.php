<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
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
    }

    private function getCachedTheme(): ?Theme
    {
        if (!$this->isInstalled()) {
            return null;
        }

        return Cache::remember('active_theme', 3600, function () {
            try {
                if (!class_exists(Theme::class) || !Schema::hasTable('visual_themes')) {
                    return null;
                }

                $themeId = Cache::get('active_theme_id');
                if ($themeId) {
                    $theme = Theme::find($themeId);
                    if ($theme) {
                        return $theme;
                    }
                    Cache::forget('active_theme_id');
                }

                $theme = Theme::where('is_default', true)->first();
                if ($theme) {
                    Cache::forever('active_theme_id', $theme->id);
                }

                return $theme;
            } catch (\Throwable $e) {
                \Log::error("Theme loading failed", ['error' => $e->getMessage()]);
                return null;
            }
        });
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
        return file_exists(storage_path('install.lock'));
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
            $mode = data_get($cfg, 'icon_mode', 'fa');

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

        if (!file_exists(storage_path('install.lock'))) {
            return null;
        }

        try {
            $id = \Illuminate\Support\Facades\Cache::get('active_theme_id');
            if ($id) {
                $theme = Theme::find($id);
            }

            if (!$theme) {
                $theme = Theme::where('is_default', true)->first();
            }
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
        $modeKey = in_array($mode, ['bootstrap', 'remix', 'tabler', 'lucide'], true) ? $mode : null;
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

    private static function getAliases(): array
    {
        return [
            'bootstrap' => [
                'bell' => 'bell', 'shopping-cart' => 'cart', 'message' => 'chat-dots',
                'search' => 'search', 'plus' => 'plus-lg', 'folder' => 'folder',
                'image' => 'image', 'file-text' => 'file-text', 'puzzle' => 'puzzle',
                'home' => 'house', 'user' => 'person', 'logout' => 'box-arrow-right',
                'login' => 'box-arrow-in-right', 'mail' => 'envelope', 'dashboard' => 'grid',
            ],
            'remix' => [
                'bell' => 'notification-3-line', 'shopping-cart' => 'shopping-cart-2-line',
                'message' => 'message-3-line', 'search' => 'search-line', 'plus' => 'add-line',
                'folder' => 'folder-3-line', 'image' => 'image-line', 'file-text' => 'file-text-line',
                'puzzle' => 'puzzle-2-line', 'home' => 'home-2-line', 'user' => 'user-3-line',
                'logout' => 'logout-box-r-line', 'login' => 'login-box-line', 'mail' => 'mail-line',
                'dashboard' => 'dashboard-2-line',
            ],
            'tabler' => [
                'bell' => 'bell', 'shopping-cart' => 'shopping-cart', 'message' => 'message',
                'search' => 'search', 'plus' => 'plus', 'folder' => 'folder', 'image' => 'photo',
                'file-text' => 'file-text', 'puzzle' => 'puzzle', 'home' => 'home', 'user' => 'user',
                'logout' => 'logout', 'login' => 'login', 'mail' => 'mail', 'dashboard' => 'layout-dashboard',
            ],
            'lucide' => [
                'bell' => 'bell', 'shopping-cart' => 'shopping-cart', 'message' => 'message-circle',
                'search' => 'search', 'plus' => 'plus', 'folder' => 'folder', 'image' => 'image',
                'file-text' => 'file-text', 'puzzle' => 'puzzle', 'home' => 'home', 'user' => 'user',
                'logout' => 'log-out', 'login' => 'log-in', 'mail' => 'mail', 'dashboard' => 'layout-dashboard',
            ],
        ];
    }

    private static function getPools(): array
    {
        return [
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
