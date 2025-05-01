<?php

namespace Modules\News\Providers;

use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('modules/Categories/Routes/admin.php'));
        $this->loadViewsFrom(base_path('modules/Categories/Views'), 'Categories');
        $this->loadMigrationsFrom(__DIR__ . '/../modules/Categories/Migrations');
    }
}
