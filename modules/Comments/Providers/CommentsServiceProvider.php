<?php

namespace Modules\Comments\Providers;

use Illuminate\Support\ServiceProvider;

class CommentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов
        $this->app->singleton('comments', function ($app) {
            return new \Modules\Comments\Services\CommentService();
        });
    }

    public function boot(): void
    {
        $modulePath = base_path('modules/Comments');

        // Загрузка маршрутов
        if (file_exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        if (file_exists($modulePath . '/Routes/api.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/api.php');
        }

        // Загрузка представлений
        if (is_dir($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Comments');
        }

        // Миграции модуля живут в единой database/migrations/.

        // Загрузка переводов
        if (is_dir($modulePath . '/Lang')) {
            $this->loadTranslationsFrom($modulePath . '/Lang', 'Comments');
        }
    }
}

