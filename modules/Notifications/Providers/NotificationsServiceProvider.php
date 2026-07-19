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

        // Миграции модуля живут в единой database/migrations/.

        // 🧩 Регистрация Blade-компонента <x-frontend-notifications />
        Blade::component('frontend-notifications', NotificationsComponent::class);
    }
}
