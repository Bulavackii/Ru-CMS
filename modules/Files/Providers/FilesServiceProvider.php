<?php

namespace Modules\Files\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 📁 Сервис-провайдер модуля Files
 */
class FilesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = base_path('modules/Files');

        // 🛣️ Подключение маршрутов
        if (file_exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        // 🖼️ Подключение представлений
        if (is_dir($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Files');
        }

        // 🗃️ Подключение миграций
        foreach (["$modulePath/Migrations", "$modulePath/Database/Migrations"] as $dir) {
            if (is_dir($dir)) {
                $this->loadMigrationsFrom($dir);
                break;
            }
        }
    }

    public function register(): void
    {
        //
    }
}

