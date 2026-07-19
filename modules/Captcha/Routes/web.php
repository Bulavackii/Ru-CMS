<?php

use Illuminate\Support\Facades\Route;
use Modules\Captcha\Controllers\CaptchaController;

// API маршруты для каптчи
Route::prefix('api/captcha')
    ->middleware(['web'])
    ->group(function () {
        // Генерация каптчи
        Route::get('/generate/{type?}', [CaptchaController::class, 'generate'])
            ->name('api.captcha.generate')
            ->where('type', 'image|slider|math|question');

        // Проверка каптчи
        Route::post('/verify', [CaptchaController::class, 'verify'])
            ->name('api.captcha.verify');

        // Рендер для Blade
        Route::get('/render/{type?}', [CaptchaController::class, 'render'])
            ->name('api.captcha.render')
            ->where('type', 'image|slider|math|question');

        // JavaScript виджет
        Route::get('/widget', [CaptchaController::class, 'widget'])
            ->name('api.captcha.widget');
    });

// Вспомогательные функции для Blade
if (!function_exists('captcha_img')) {
    function captcha_img($type = 'image', $options = [])
    {
        $service = app('captcha');
        $captcha = $service->generate($type, $options);
        return $captcha['html'];
    }
}

if (!function_exists('captcha_js')) {
    function captcha_js($selector = '#captcha-container', $type = 'image')
    {
        $service = app('captcha');
        return $service->renderJS($selector, $type);
    }
}
