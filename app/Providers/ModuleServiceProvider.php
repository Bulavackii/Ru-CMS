<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\System\Models\Module;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Легаси-модули (Categories/News/Slideshow/Messages/Payments/Delivery/
        // Menu/Notifications/Accessibility/NewsIO/Visual/Seo/Localization и т.д.)
        // грузим безусловно, а не только после isInstalled(). Причина:
        // базовый layout (frontend.blade.php) и глобальные Blade-компоненты
        // (ThemeServiceProvider) обращаются к Menu::/Notifications:: и т.п.
        // на КАЖДОЙ странице, включая страницы до установки — без этого
        // view-namespace не существует и любой GET / падает с 500.
        //
        // Миграции сюда больше не входят: все миграции проекта (в т.ч.
        // модульные) живут в единой database/migrations/ и подхватываются
        // Laravel автоматически, без какой-либо ручной регистрации путей.
        $this->loadLegacyModules(base_path('modules'));

        if (!$this->isInstalled()) {
            return;
        }

        $this->syncModuleMetadata();
        $this->loadActiveModules();
    }

    private function isInstalled(): bool
    {
        return file_exists(storage_path('install.lock'))
            && class_exists(Module::class)
            && Schema::hasTable('modules');
    }

    private function syncModuleMetadata(): void
    {
        $moduleDirectories = File::directories(base_path('modules'));

        foreach ($moduleDirectories as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';

            if (!File::exists($moduleJsonPath)) {
                continue;
            }

            try {
                $metadata = json_decode(File::get($moduleJsonPath), true);
            } catch (\Throwable $e) {
                continue;
            }

            if (!is_array($metadata) || !isset($metadata['title'], $metadata['priority'])) {
                continue;
            }

            $module = Module::where('name', $moduleName)->first();
            if (!$module) {
                continue;
            }

            $module->title = $metadata['title'];
            $module->priority = $metadata['priority'];
            $module->save();
        }
    }

    private function loadActiveModules(): void
    {
        $modulesPath = base_path('modules');
        $activeModules = Module::where('active', true)->pluck('name');

        foreach ($activeModules as $moduleName) {
            $base = $modulesPath . '/' . $moduleName;

            if (!is_dir($base)) {
                Module::where('name', $moduleName)->delete();
                continue;
            }

            $this->loadModule($base, $moduleName);
        }

        // Загрузка неавтоматизированных модулей
        $this->loadLegacyModules($modulesPath);
    }

    private function loadModule(string $base, string $moduleName): void
    {
        // Маршруты
        if (is_file("$base/Routes/web.php")) {
            $this->loadRoutesFrom("$base/Routes/web.php");
        }

        // Вьюхи
        foreach (["$base/Views", "$base/Resources/views"] as $dir) {
            if (is_dir($dir)) {
                $this->loadViewsFrom($dir, $moduleName);
            }
        }

        // Переводы
        foreach (["$base/Lang", "$base/Resources/lang"] as $dir) {
            if (is_dir($dir)) {
                $this->loadTranslationsFrom($dir, $moduleName);
            }
        }

        // Провайдеры
        $moduleJson = "$base/module.json";
        if (is_file($moduleJson)) {
            try {
                $meta = json_decode(file_get_contents($moduleJson), true) ?: [];
                if (!empty($meta['providers']) && is_array($meta['providers'])) {
                    foreach ($meta['providers'] as $providerClass) {
                        if (class_exists($providerClass)) {
                            $this->app->register($providerClass);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to register module providers for {$moduleName}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function loadLegacyModules(string $modulesPath): void
    {
        // Модули, которые требуют особой обработки
        $legacyModules = [
            'Users' => ['routes' => true, 'views' => true],
            'Search' => ['routes' => true, 'views' => true],
            'Categories' => ['views' => true],
            'News' => ['views' => true],
            'Slideshow' => ['routes' => true, 'views' => true],
            'Messages' => ['routes' => true, 'views' => true],
            'Payments' => ['routes' => true, 'views' => true],
            'Delivery' => ['routes' => true, 'views' => true],
            'Menu' => ['routes' => true, 'views' => true],
            'Notifications' => ['views' => true],
            'Accessibility' => ['routes' => true, 'views' => true],
            'NewsIO' => ['routes' => true, 'views' => true],
            'Localization' => ['routes' => true, 'views' => true],
        ];

        foreach ($legacyModules as $module => $config) {
            $base = $modulesPath . '/' . $module;

            if (!is_dir($base)) {
                continue;
            }

            if ($config['routes'] ?? false) {
                $routeFile = "$base/Routes/web.php";
                if (is_file($routeFile)) {
                    $this->loadRoutesFrom($routeFile);
                }
            }

            if ($config['views'] ?? false) {
                $viewDirs = ["$base/Views", "$base/Resources/views"];
                foreach ($viewDirs as $dir) {
                    if (is_dir($dir)) {
                        // Регистрируем namespace в обоих регистрах: одни
                        // контроллеры ссылаются на view('Categories::...')
                        // (как в файловой системе), другие — на 'messages::'
                        // в нижнем регистре. Дублирование безвредно.
                        $this->loadViewsFrom($dir, $module);
                        if (strtolower($module) !== $module) {
                            $this->loadViewsFrom($dir, strtolower($module));
                        }
                    }
                }
            }
        }

        // Особый случай: Visual (ручная регистрация)
        $this->loadVisualModule($modulesPath);

        // Особый случай: Seo (если не активирован через БД)
        $this->loadSeoModule($modulesPath);
    }

    private function loadVisualModule(string $modulesPath): void
    {
        $visualBase = $modulesPath . '/Visual';

        if (!is_dir($visualBase)) {
            return;
        }

        if (is_file("$visualBase/Routes/web.php")) {
            $this->loadRoutesFrom("$visualBase/Routes/web.php");
        }

        foreach (["$visualBase/Views", "$visualBase/Resources/views"] as $dir) {
            if (is_dir($dir)) {
                $this->loadViewsFrom($dir, 'Visual');
            }
        }

        foreach (["$visualBase/Lang", "$visualBase/Resources/lang"] as $dir) {
            if (is_dir($dir)) {
                $this->loadTranslationsFrom($dir, 'Visual');
            }
        }
    }

    private function loadSeoModule(string $modulesPath): void
    {
        try {
            $seoBase = $modulesPath . '/Seo';

            if (!is_dir($seoBase)) {
                return;
            }

            $shouldManuallyLoadSeo = true;
            if (class_exists(\Modules\System\Models\Module::class) && Schema::hasTable('modules')) {
                $shouldManuallyLoadSeo = !Module::where('name', 'Seo')->where('active', true)->exists();
            }

            if (!$shouldManuallyLoadSeo) {
                return;
            }

            if (is_file("$seoBase/Routes/web.php")) {
                $this->loadRoutesFrom("$seoBase/Routes/web.php");
            }

            foreach (["$seoBase/Views", "$seoBase/Resources/views"] as $dir) {
                if (is_dir($dir)) {
                    $this->loadViewsFrom($dir, 'seo');
                }
            }

            foreach (["$seoBase/Lang", "$seoBase/Resources/lang"] as $dir) {
                if (is_dir($dir)) {
                    $this->loadTranslationsFrom($dir, 'seo');
                }
            }

            if (class_exists(\Modules\Seo\Providers\SeoServiceProvider::class)) {
                $this->app->register(\Modules\Seo\Providers\SeoServiceProvider::class);
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to load SEO module", ['error' => $e->getMessage()]);
        }
    }
}
