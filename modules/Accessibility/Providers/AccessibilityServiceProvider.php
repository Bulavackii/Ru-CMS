<?php

namespace Modules\Accessibility\Providers;

use Illuminate\Support\ServiceProvider;

class AccessibilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modulePath = base_path('modules/Accessibility');

        // 🔁 Подключение маршрутов, шаблонов, миграций
        if (file_exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        if (is_dir($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Accessibility');
        }

        // Миграции модуля живут в единой database/migrations/.
    }
}
