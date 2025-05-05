<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\View\Components\Frontend\Notifications;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'Notifications');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // 👇 Это правильное место
        Blade::component('frontend.notifications', Notifications::class);
    }
}
