<?php

namespace Modules\Slideshow;

use Illuminate\Support\ServiceProvider;

class SlideshowServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Ничего пока не нужно
    }

    public function boot()
    {
        // Подключаем views с namespace 'Slideshow'
        $this->loadViewsFrom(__DIR__ . '/Views', 'Slideshow');

        // При необходимости можно подключить маршруты
        if (file_exists(__DIR__ . '/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        }

        // Подключаем миграции
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
