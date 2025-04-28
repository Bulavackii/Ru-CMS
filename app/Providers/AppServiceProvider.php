<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = base_path('modules');

        foreach (glob("{$modulesPath}/*", GLOB_ONLYDIR) as $modulePath) {
            $moduleName = basename($modulePath);

            // Web маршруты
            $webRoutes = "{$modulePath}/Routes/web.php";
            if (file_exists($webRoutes)) {
                Route::middleware('web')->group($webRoutes);
            }

            // API маршруты
            $apiRoutes = "{$modulePath}/Routes/api.php";
            if (file_exists($apiRoutes)) {
                Route::prefix('api')->middleware('api')->group($apiRoutes);
            }

            // Миграции
            if (is_dir($modulePath.'/Migrations')) {
                $this->loadMigrationsFrom($modulePath.'/Migrations');
            }

            // Виды
            if (is_dir($modulePath.'/Views')) {
                $this->loadViewsFrom($modulePath.'/Views', $moduleName);
            }

            // Переводы
            if (is_dir($modulePath.'/Lang')) {
                $this->loadTranslationsFrom($modulePath.'/Lang', $moduleName);
            }
        }
    }
}
