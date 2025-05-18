<?php

namespace Modules\News\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

/**
 * 📰 NewsServiceProvider
 *
 * Сервис-провайдер модуля новостей (`News`).
 * Отвечает за:
 * 🔹 Миграции
 * 🔹 Регистрацию Blade-компонентов
 */
class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * 📦 Миграции модуля
         * Загружаются из `modules/News/Migrations`
         */
        $this->loadMigrationsFrom(base_path('modules/News/Migrations'));

        /**
         * 🧩 Регистрация Blade-компонентов
         *
         * Позволяет использовать:
         * <x-news::ComponentName />
         * если компоненты лежат в:
         * modules/News/Views/Components/...
         */
        Blade::componentNamespace('Modules\\News\\Views\\Components', 'news');

        /**
         * 🎯 Регистрация отдельного компонента вручную
         * Позволяет использовать: <x-template-badge />
         * и ссылается на шаблон: resources/views/vendor/News/admin/template-badge.blade.php
         */
        Blade::component('News::admin.template-badge', 'template-badge');
    }
}
