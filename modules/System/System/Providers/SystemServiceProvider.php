<?php

namespace Modules\System\Providers;

use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'System');
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../Lang', 'System');
        $this->loadMigrationsFrom(__DIR__.'../Migrations');
    }
}
