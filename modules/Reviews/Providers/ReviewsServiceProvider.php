<?php

namespace Modules\Reviews\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reviews\Models\Review;

class ReviewsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Регистрация сервисов
        $this->app->bind('reviews', function ($app) {
            return new \Modules\Reviews\Services\ReviewService();
        });
    }

    public function boot()
    {
        // Маршруты
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Представления
        $this->loadViewsFrom(__DIR__ . '/../Views', 'Reviews');

        // Миграции модуля живут в единой database/migrations/.

        // Переводы
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'Reviews');

        // Публикация конфигов
        $this->publishes([
            __DIR__ . '/../Config/reviews.php' => config_path('reviews.php'),
        ], 'reviews-config');
    }
}
