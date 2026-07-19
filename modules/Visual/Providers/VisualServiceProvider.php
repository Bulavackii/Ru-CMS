<?php

namespace Modules\Visual\Providers;

use Illuminate\Support\ServiceProvider;

class VisualServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'Visual');
        // Миграции модуля живут в единой database/migrations/.
    }

    public function register(): void
    {
        //
    }
}
