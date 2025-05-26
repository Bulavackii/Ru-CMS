<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    copy(__DIR__ . '/../.env.example', $envPath);
}

$env = file_get_contents($envPath);

// 👇 Проверка: нет строки, пустая или явно placeholder
if (
    !preg_match('/^APP_KEY=.*$/m', $env) ||
    preg_match('/^APP_KEY=(null|placeholder)?$/m', $env)
) {
    $key = 'base64:' . base64_encode(random_bytes(32));

    if (preg_match('/^APP_KEY=.*$/m', $env)) {
        $env = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $env);
    } else {
        $env .= "\nAPP_KEY={$key}";
    }

    file_put_contents($envPath, $env);
}

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
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )

    // 🛡️ Middleware-алиасы (короткие имена)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'skip.install.db' => \App\Http\Middleware\SkipDatabaseForInstall::class, // 💡 для отключения подключения к БД при установке
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
$app->register(Modules\Notifications\Providers\NotificationsServiceProvider::class);
$app->register(Modules\Menu\Providers\MenuServiceProvider::class);
$app->register(Modules\Install\InstallServiceProvider::class);
$app->register(Modules\Accessibility\Providers\AccessibilityServiceProvider::class);
