<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Modules\Notifications\View\Components\Frontend\NotificationsComponent;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'Notifications');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Blade::component('frontend-notifications', 'Modules\\Notifications\\View\\Components\\Frontend\\NotificationsComponent');
    }
}
