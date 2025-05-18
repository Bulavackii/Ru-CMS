<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

/**
 * 🧩 Сервис-провайдер модуля Menu
 *
 * 🔹 Подключает маршруты, представления и миграции модуля
 */
class MenuServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot()
     *
     * 🔌 Загружает ресурсы модуля при инициализации
     */
    public function boot(): void
    {
        $modulePath = base_path('modules/Menu');

        // 📍 Подключение маршрутов
        if (File::exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        // 🖼️ Подключение представлений (views)
        if (File::exists($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Menu');
        }

        // 🗄️ Подключение миграций
        if (File::exists($modulePath . '/Database/Migrations')) {
            $this->loadMigrationsFrom($modulePath . '/Database/Migrations');
        }

        // 📌 (опционально) Подключение трансляций, компонентов, конфигов — если нужно
        // $this->loadTranslationsFrom($modulePath . '/Lang', 'Menu');
        // $this->mergeConfigFrom($modulePath . '/Config/menu.php', 'menu');
    }

    /**
     * 📦 Метод register()
     *
     * 🧱 Регистрация зависимостей, биндингов и сервисов
     */
    public function register(): void
    {
        // Здесь можно регистрировать кастомные сервисы или фасады модуля
    }
}
