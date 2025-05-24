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

        // ✅ Синхронизация title и priority из module.json
        $this->syncModuleMetadata();

        /**
         * 🛑 Редирект на /install, если нет install.lock
         */
        \View::addNamespace('Install', base_path('modules/Install/Views'));

        if (!app()->runningInConsole() && !file_exists(storage_path('install.lock'))) {
            if (!request()->is('install*')) {
                redirect('/install')->send(); // принудительный переход
            }
            return;
        }

        /**
         * 🔁 Загрузка всех активных модулей
         */
        if (class_exists(Module::class) && Schema::hasTable('modules')) {
            $activeModules = Module::where('active', true)->pluck('name');

            foreach ($activeModules as $moduleName) {
                $base = $modulesPath . '/' . $moduleName;

                if (is_dir($base)) {
                    if (file_exists("{$base}/Routes/web.php")) {
                        $this->loadRoutesFrom("{$base}/Routes/web.php");
                    }

                    if (is_dir("{$base}/Views")) {
                        $this->loadViewsFrom("{$base}/Views", $moduleName);
                    }

                    if (is_dir("{$base}/Migrations")) {
                        $this->loadMigrationsFrom("{$base}/Migrations");
                    }

                    if (is_dir("{$base}/Lang")) {
                        $this->loadTranslationsFrom("{$base}/Lang", $moduleName);
                    }
                } else {
                    // 🧹 Чистим базу, если модуль удалён с диска
                    Module::where('name', $moduleName)->delete();
                }
            }
        }

        /**
         * 🔧 Ручная регистрация модулей
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

    /**
     * 🔁 Синхронизирует поля title и priority всех модулей
     * из файлов module.json (если существуют и валидны)
     */
    protected function syncModuleMetadata(): void
    {
        $moduleDirectories = File::directories(base_path('modules'));

        foreach ($moduleDirectories as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';

            if (!File::exists($moduleJsonPath)) {
                continue;
            }

            try {
                $jsonContent = File::get($moduleJsonPath);
                $metadata = json_decode($jsonContent, true);
            } catch (\Exception $e) {
                continue;
            }

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($metadata)) {
                continue;
            }

            if (!isset($metadata['title']) || !isset($metadata['priority'])) {
                continue;
            }

            $module = Module::where('name', $moduleName)->first();
            if (!$module) {
                continue;
            }

            $module->title = $metadata['title'];
            $module->priority = $metadata['priority'];
            $module->save();
        }
    }
}
