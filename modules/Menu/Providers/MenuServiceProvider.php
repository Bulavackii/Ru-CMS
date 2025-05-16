<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

class MenuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = base_path('modules/Menu');

        // Подключение маршрутов
        if (File::exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        // Подключение представлений
        if (File::exists($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Menu');
        }

        // Подключение миграций
        if (File::exists($modulePath . '/Database/Migrations')) {
            $this->loadMigrationsFrom($modulePath . '/Database/Migrations');
        }
    }

    public function register(): void
    {
        //
    }
}
