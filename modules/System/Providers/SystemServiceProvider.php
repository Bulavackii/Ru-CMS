<?php

namespace Modules\System\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 🧩 SystemServiceProvider
 *
 * Провайдер модуля System: подключает маршруты, шаблоны, миграции и переводы.
 */
class SystemServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot() — регистрация ресурсов модуля
     */
    public function boot(): void
    {
        // 🌐 Подключение маршрутов
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // 🖼️ Подключение Blade-представлений с namespace 'System'
        $this->loadViewsFrom(__DIR__ . '/../Views', 'System');

        // 🗃️ Подключение миграций
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        // 🌍 Подключение переводов
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'System');
    }
}
