<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

$app->register(Modules\System\Providers\SystemServiceProvider::class);
$app->register(Modules\News\Providers\NewsServiceProvider::class);
$app->register(Modules\Slideshow\SlideshowServiceProvider::class);
$app->register(Modules\Messages\Providers\MessagesServiceProvider::class);
$app->register(Modules\Notifications\NotificationsServiceProvider::class);
