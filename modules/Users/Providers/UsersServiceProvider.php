<?php

namespace Modules\Users\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 👥 UsersServiceProvider
 * Отвечает за загрузку маршрутов, миграций и представлений модуля пользователей
 */
class UsersServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot()
     * Загружает все необходимые ресурсы модуля "Users"
     */
    public function boot(): void
    {
        // 🌐 Маршруты
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // 🗃️ Миграции
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        // 🖼️ Представления (Views)
        $this->loadViewsFrom(module_path('Users', 'Resources/views'), 'users');
    }

    /**
     * 📦 Метод register()
     * Зарегистрировать дополнительные биндинги или зависимости (пока не используется)
     */
    public function register(): void
    {
        //
    }
}
