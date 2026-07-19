<?php

namespace Modules\Localization\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Localization\Services\LocalizationService;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot() — регистрация ресурсов модуля
     */
    public function boot(): void
    {
        // 🛣️ Подключение маршрутов
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // 🖼️ Подключение Blade-представлений с namespace 'Localization'
        $this->loadViewsFrom(__DIR__ . '/../Views', 'Localization');

        // 🗃️ Подключение миграций
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // 🌍 Подключение переводов
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'Localization');

        // ⚙️ Публикация конфигурации
        $this->publishes([
            __DIR__ . '/../Config/localization.php' => config_path('localization.php'),
        ], 'localization-config');

        // 📦 Публикация миграций (если нужно)
        $this->publishes([
            __DIR__ . '/../Database/Migrations/' => database_path('migrations'),
        ], 'localization-migrations');
    }

    /**
     * 📦 Метод register() — регистрация сервисов
     */
    public function register(): void
    {
        // Регистрация сервиса как синглтона
        $this->app->singleton(LocalizationService::class, function ($app) {
            return new LocalizationService();
        });

        // Алиас для удобного доступа
        $this->app->alias(LocalizationService::class, 'localization');
    }
}
