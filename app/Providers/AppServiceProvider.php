<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Modules\System\Models\Module;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\View\Components\Frontend\NotificationsComponent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Здесь можно регистрировать сервисы, если потребуется
    }

    public function boot(): void
    {
        $modulesPath = base_path('modules');

        /**
         * 🛑 Если CMS ещё не установлена (нет файла install.lock),
         * то всё приложение автоматически редиректит на /install,
         * кроме самих маршрутов install/*
         */

         \View::addNamespace('Install', base_path('modules/Install/Views'));
         
        if (!app()->runningInConsole() && !file_exists(storage_path('install.lock'))) {
            if (!request()->is('install*')) {
                redirect('/install')->send(); // принудительный переход
            }

            return; // ❗ Прерываем дальнейшую загрузку модулей и провайдеров
        }

        /**
         * 🔁 Автоматическая загрузка всех активных модулей после установки
         * Подгружаются: маршруты, представления, миграции, переводы
         */
        if (class_exists(Module::class) && Schema::hasTable('modules')) {
            $activeModules = Module::where('active', true)->pluck('name');

            foreach ($activeModules as $module) {
                $base = "{$modulesPath}/{$module}";

                if (File::exists("{$base}/Routes/web.php")) {
                    $this->loadRoutesFrom("{$base}/Routes/web.php");
                }

                if (File::exists("{$base}/Views")) {
                    $this->loadViewsFrom("{$base}/Views", $module);
                }

                if (File::isDirectory("{$base}/Migrations")) {
                    $this->loadMigrationsFrom("{$base}/Migrations");
                }

                if (File::isDirectory("{$base}/Lang")) {
                    $this->loadTranslationsFrom("{$base}/Lang", $module);
                }
            }
        }

        /**
         * 🔧 Ручная регистрация модулей, не зависящих от `modules` таблицы
         */
        $this->loadRoutesFrom("{$modulesPath}/Users/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Users/Views", 'users');

        $this->loadRoutesFrom("{$modulesPath}/Search/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Search/Views", 'Search');

        $this->loadViewsFrom("{$modulesPath}/Categories/Views", 'Categories');
        $this->loadViewsFrom("{$modulesPath}/News/Views", 'News');

        $this->loadRoutesFrom("{$modulesPath}/Slideshow/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Slideshow/Views", 'Slideshow');
        $this->loadMigrationsFrom("{$modulesPath}/Slideshow/Migrations");

        $this->loadRoutesFrom("{$modulesPath}/Messages/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Messages/Views", 'messages');
        $this->loadMigrationsFrom("{$modulesPath}/Messages/Migrations");

        $this->loadRoutesFrom("{$modulesPath}/Payments/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Payments/Views", 'Payments');
        $this->loadMigrationsFrom("{$modulesPath}/Payments/Migrations");

        $this->loadRoutesFrom("{$modulesPath}/Delivery/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Delivery/Views", 'Delivery');
        $this->loadMigrationsFrom("{$modulesPath}/Delivery/Migrations");

        $this->loadRoutesFrom("{$modulesPath}/Menu/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Menu/Views", 'Menu');
        $this->loadMigrationsFrom("{$modulesPath}/Menu/Migrations");

        /**
         * 🔔 Уведомления — компоненты и шаблоны
         */
        $this->loadViewsFrom("{$modulesPath}/Notifications/Resources/views", 'Notifications');
        Blade::component('frontend-notifications', NotificationsComponent::class);

        /**
         * 📩 View composer для глобального подключения уведомлений
         */
        View::composer('*', function ($view) {
            $view->with('notifications', Notification::where('enabled', true)->get());
        });
    }
}
