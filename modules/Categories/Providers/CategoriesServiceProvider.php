<?php

namespace Modules\Categories\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 📦 Service Provider модуля Categories
 *
 * Регистрирует всё необходимое для работы модуля категорий:
 * маршруты, миграции, шаблоны.
 */
class CategoriesServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot вызывается автоматически при инициализации провайдера
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 🖼️ Подключение шаблонов представлений (Blade)
        |--------------------------------------------------------------------------
        |
        | Все шаблоны из папки `modules/Categories/Views` можно использовать через
        | пространство имён `Categories::` в Blade-шаблонах:
        | Например: `Categories::admin.index`
        |
        */
        $this->loadViewsFrom(base_path('modules/Categories/Views'), 'Categories');

        /*
        |--------------------------------------------------------------------------
        | 🛣️ Подключение маршрутов
        |--------------------------------------------------------------------------
        |
        | Подключаем файл `web.php`, содержащий маршруты для админки или фронтенда.
        |
        */
        $this->loadRoutesFrom(base_path('modules/Categories/Routes/web.php'));

        // Миграции модуля живут в единой database/migrations/ вместе со
        // всеми остальными и подхватываются Laravel автоматически.
    }
}
