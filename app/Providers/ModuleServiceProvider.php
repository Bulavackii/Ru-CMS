<?php

namespace Modules\News\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 📰 NewsServiceProvider
 *
 * Сервис-провайдер для модуля новостей (`News`).
 * Загружает:
 * - маршруты
 * - представления
 * - миграции
 */
class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 📦 Загружаем маршруты модуля News
        $this->loadRoutesFrom(base_path('modules/News/Routes/web.php'));

        // 🖼️ Подключаем Blade-шаблоны для News::...
        $this->loadViewsFrom(base_path('modules/News/Views'), 'News');

        // 🧬 Подключаем миграции
        $this->loadMigrationsFrom(base_path('modules/News/Migrations'));
    }
}
