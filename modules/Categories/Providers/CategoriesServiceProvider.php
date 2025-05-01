<?php

namespace Modules\Categories\Providers;

use Illuminate\Support\ServiceProvider;

class CategoriesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Вьюхи
        $this->loadViewsFrom(base_path('modules/Categories/Views'), 'Categories');

        // Роуты
        $this->loadRoutesFrom(base_path('modules/Categories/Routes/web.php'));

        // Миграции
        $this->loadMigrationsFrom(base_path('modules/Categories/Migrations'));
    }
}
