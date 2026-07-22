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

        // Миграции модуля живут в единой database/migrations/.

        // 🌍 Подключение переводов
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'Localization');

        // ⚙️ Домёрживаем конфиг модуля с config/localization.php (тот же баг,
        // что чинили у Captcha: без mergeConfigFrom() ключи, которых нет в
        // опубликованном конфиге — например preset_countries, — тихо
        // возвращали дефолт [], и импорт стран/автозаполнение пресетов не
        // работали). Ключи, уже объявленные в config/localization.php
        // (date_formats, time_formats и т.д.), приоритета не теряют —
        // array_merge оставляет их как есть.
        $this->mergeConfigFrom(__DIR__ . '/../Config/localization.php', 'localization');

        // ⚙️ Публикация конфигурации
        $this->publishes([
            __DIR__ . '/../Config/localization.php' => config_path('localization.php'),
        ], 'localization-config');
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
