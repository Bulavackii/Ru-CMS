<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\System\Models\Module;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\View\Components\Frontend\NotificationsComponent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modulesPath = base_path('modules');

        // 🔁 Автоподключение активных модулей
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

        // ✅ Ручные подключения
        $this->loadRoutesFrom("{$modulesPath}/Users/Routes/web.php");
        $this->loadViewsFrom(base_path('modules/Users/Views'), 'users');

        $this->loadRoutesFrom("{$modulesPath}/Search/Routes/web.php");
        $this->loadViewsFrom("{$modulesPath}/Search/Views", 'Search');

        $this->loadViewsFrom(base_path('modules/Categories/Views'), 'Categories');
        $this->loadViewsFrom(base_path('modules/News/Views'), 'News');

        $this->loadRoutesFrom(base_path('modules/Slideshow/Routes/web.php'));
        $this->loadViewsFrom(base_path('modules/Slideshow/Views'), 'Slideshow');
        $this->loadMigrationsFrom(base_path('modules/Slideshow/Migrations'));

        $this->loadRoutesFrom(base_path('modules/Messages/Routes/web.php'));
        $this->loadViewsFrom(base_path('modules/Messages/Views'), 'messages');
        $this->loadMigrationsFrom(base_path('modules/Messages/Migrations'));

        $this->loadRoutesFrom(base_path('modules/Payments/Routes/web.php'));
        $this->loadViewsFrom(base_path('modules/Payments/Views'), 'Payments');
        $this->loadMigrationsFrom(base_path('modules/Payments/Migrations'));

        $this->loadRoutesFrom(base_path('modules/Delivery/Routes/web.php'));
        $this->loadViewsFrom(base_path('modules/Delivery/Views'), 'Delivery');
        $this->loadMigrationsFrom(base_path('modules/Delivery/Migrations'));

        // ✅ Уведомления — views + регистрация Blade-компонента
        $this->loadViewsFrom(base_path('modules/Notifications/Resources/views'), 'Notifications');
        Blade::component('frontend-notifications', NotificationsComponent::class);

        // ✅ View composer для глобального доступа к уведомлениям
        View::composer('*', function ($view) {
            $view->with('notifications', Notification::where('enabled', true)->get());
        });
    }
}
