<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * 🚀 Инициализация Laravel-приложения (Laravel 11)
 *
 * Здесь настраиваются:
 * - базовый путь
 * - маршруты
 * - консольные команды
 * - middleware-алиасы
 * - обработка исключений
 * - регистрация сервис-провайдеров
 */

return Application::configure(basePath: dirname(__DIR__))

    // 🔁 Маршруты: web, console, health-check
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up', // 💓 эндпоинт для проверки доступности приложения
    )

    // 🛡️ Middleware-алиасы (короткие имена)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })

    // ⚠️ Обработка исключений (настраивается при необходимости)
    ->withExceptions(function (Exceptions $exceptions) {
        // Можно добавить кастомные обработчики, логирование и т.п.
    })

    // 🧱 Создание приложения (возвращает Application)
    ->create();

// 🧩 Регистрация модульных провайдеров (ручная, без авто-дискавери)
$app->register(Modules\System\Providers\SystemServiceProvider::class);
$app->register(Modules\News\Providers\NewsServiceProvider::class);
$app->register(Modules\Slideshow\SlideshowServiceProvider::class);
$app->register(Modules\Messages\Providers\MessagesServiceProvider::class);
$app->register(Modules\Notifications\NotificationsServiceProvider::class);
$app->register(Modules\Menu\Providers\MenuServiceProvider::class);
