<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\System\Models\Module;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Модули из loadLegacyModules(), для которых нужно грузить миграции.
     * Вынесено в константу, чтобы список был доступен и до установки
     * (см. loadLegacyModuleMigrations()), и после (loadLegacyModules()).
     */
    private const LEGACY_MIGRATION_MODULES = [
        'Categories', 'News', 'Slideshow', 'Messages', 'Payments',
        'Delivery', 'Menu', 'Accessibility', 'NewsIO', 'Visual',
    ];

    public function boot(): void
    {
        // Миграции легаси-модулей регистрируем всегда, даже до установки:
        // isInstalled() требует существования таблицы modules, а её как раз
        // создают миграции — без этого вызова свежая БД никогда не увидит
        // миграции модулей вроде Categories/News на первом migrate:fresh.
        // Повторная регистрация той же директории (уже после установки, из
        // loadActiveModules()) безвредна — Laravel определяет "к выполнению"
        // миграции по имени файла, что уже отмечено выполненным, не запускает.
        $this->loadLegacyModuleMigrations(base_path('modules'));

        if (!$this->isInstalled()) {
            return;
        }

        $this->syncModuleMetadata();
        $this->loadActiveModules();
    }

    private function loadLegacyModuleMigrations(string $modulesPath): void
    {
        foreach (self::LEGACY_MIGRATION_MODULES as $module) {
            $base = $modulesPath . '/' . $module;
            foreach (["$base/Migrations", "$base/Database/Migrations"] as $dir) {
                if (is_dir($dir)) {
                    $this->loadMigrationsFrom($dir);
                }
            }
        }
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
        $this->loadLegacyModules($modulesPath, $activeModules->all());
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

        // Миграции
        foreach (["$base/Migrations", "$base/Database/Migrations"] as $dir) {
            if (is_dir($dir)) {
                $this->loadMigrationsFrom($dir);
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

    /**
     * @param string[] $alreadyActiveModules Модули, уже обработанные в loadActiveModules()
     *   через loadModule() — их миграции грузить второй раз нельзя (упадёт
     *   "table already exists"). Routes/views грузим повторно и для них тоже:
     *   loadModule() и этот метод регистрируют view-неймспейс в разном
     *   регистре (например 'Messages' vs 'messages::' в контроллерах),
     *   так что вторая регистрация тут иногда единственная рабочая.
     */
    private function loadLegacyModules(string $modulesPath, array $alreadyActiveModules = []): void
    {
        // Модули, которые требуют особой обработки
        $legacyModules = [
            'Users' => ['routes' => true, 'views' => true],
            'Search' => ['routes' => true, 'views' => true],
            'Categories' => ['views' => true, 'migrations' => true],
            'News' => ['views' => true, 'migrations' => true],
            'Slideshow' => ['routes' => true, 'views' => true, 'migrations' => true],
            'Messages' => ['routes' => true, 'views' => true, 'migrations' => true],
            'Payments' => ['routes' => true, 'views' => true, 'migrations' => true],
            'Delivery' => ['routes' => true, 'views' => true, 'migrations' => true],
            'Menu' => ['routes' => true, 'views' => true, 'migrations' => true],
            'Notifications' => ['views' => true],
            'Accessibility' => ['routes' => true, 'views' => true, 'migrations' => true],
            'NewsIO' => ['routes' => true, 'views' => true, 'migrations' => true],
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
                        $this->loadViewsFrom($dir, strtolower($module));
                    }
                }
            }

            if (($config['migrations'] ?? false) && !in_array($module, $alreadyActiveModules, true)) {
                $migrationDirs = ["$base/Migrations", "$base/Database/Migrations"];
                foreach ($migrationDirs as $dir) {
                    if (is_dir($dir)) {
                        $this->loadMigrationsFrom($dir);
                    }
                }
            }
        }

        // Особый случай: Visual (ручная регистрация)
        $this->loadVisualModule($modulesPath, $alreadyActiveModules);

        // Особый случай: Seo (если не активирован через БД)
        $this->loadSeoModule($modulesPath);
    }

    private function loadVisualModule(string $modulesPath, array $alreadyActiveModules = []): void
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

        if (!in_array('Visual', $alreadyActiveModules, true)) {
            foreach (["$visualBase/Migrations", "$visualBase/Database/Migrations"] as $dir) {
                if (is_dir($dir)) {
                    $this->loadMigrationsFrom($dir);
                }
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

            foreach (["$seoBase/Migrations", "$seoBase/Database/Migrations"] as $dir) {
                if (is_dir($dir)) {
                    $this->loadMigrationsFrom($dir);
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
