<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Modules\System\Models\Module;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modulesPath = base_path('modules');

        // Подключение активных модулей — безопасно
        if (class_exists(Module::class) && Schema::hasTable('modules')) {
            $activeModules = Module::where('active', true)->pluck('name');

            foreach ($activeModules as $module) {
                $base = "{$modulesPath}/{$module}";

                if (File::exists("{$base}/Routes/web.php")) {
                    $this->loadRoutesFrom("{$base}/Routes/web.php");
                }

                if (File::exists("{$base}/Views")) {
                    $this->loadViewsFrom("{$base}/Views", $module);
                }

                if (File::isDirectory("{$base}/Migrations")) {
                    $this->loadMigrationsFrom("{$base}/Migrations");
                }

                if (File::isDirectory("{$base}/Lang")) {
                    $this->loadTranslationsFrom("{$base}/Lang", $module);
                }
            }
        }

        // ✅ Прямое подключение Users
        $this->loadRoutesFrom("{$modulesPath}/Users/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Users/Views", 'Users');

        // ✅ Прямое подключение Search
        $this->loadRoutesFrom("{$modulesPath}/Search/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Search/Views", 'Search');

        // ✅ Прямое подключение Категорий
        $this->loadViewsFrom(base_path('modules/Categories/Views'), 'Categories');

        // ✅ Прямое подключение Новостей
        $this->loadViewsFrom(base_path('modules/News/Views'), 'News');

        // ✅ Прямое подключение Слайдшоу
        $this->loadRoutesFrom(base_path('modules/Slideshow/Routes/web.php'));
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(base_path('modules/Slideshow/Views'), 'Slideshow');
    }
}
