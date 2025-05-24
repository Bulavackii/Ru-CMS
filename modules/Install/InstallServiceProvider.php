<?php

namespace Modules\Install;

use Illuminate\Support\ServiceProvider;

class InstallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $base = __DIR__;

        // 📦 Маршруты инсталлятора
        if (file_exists($base . '/Routes/web.php')) {
            $this->loadRoutesFrom($base . '/Routes/web.php');
        }

        // 🖼 Представления (Blade)
        if (is_dir($base . '/Views')) {
            $this->loadViewsFrom($base . '/Views', 'Install');
        }

        // 🔐 Миграции (если появятся в будущем)
        if (is_dir($base . '/Migrations')) {
            $this->loadMigrationsFrom($base . '/Migrations');
        }
    }
}
