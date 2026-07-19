<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Modules\Notifications\View\Components\Frontend\NotificationsComponent;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * 🚀 Метод boot вызывается при загрузке модуля
     */
    public function boot(): void
    {
        // 🖼️ Загрузка Blade-шаблонов
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'Notifications');

        // 🛣️ Загрузка маршрутов модуля
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // 🧬 Загрузка миграций
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // 🧩 Регистрация Blade-компонента <x-frontend-notifications />
        Blade::component('frontend-notifications', NotificationsComponent::class);
    }
}
