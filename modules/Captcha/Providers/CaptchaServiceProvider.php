<?php

namespace Modules\Captcha\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Без mergeConfigFrom() config('captcha.*') всегда падает на
        // fallback-значение из самого вызова config(), а не читает
        // modules/Captcha/Config/captcha.php — CAPTCHA_ENABLED в .env
        // тогда попросту ни на что не влияет.
        $this->mergeConfigFrom(__DIR__ . '/../Config/captcha.php', 'captcha');

        // Регистрация сервиса
        $this->app->bind('captcha', function ($app) {
            return new \Modules\Captcha\Services\CaptchaService();
        });

        // Регистрация валидационного правила
        Validator::extend('captcha', function ($attribute, $value, $parameters, $validator) {
            $type = $parameters[0] ?? 'image';
            $service = app('captcha');
            return $service->verify($value, $type);
        });
    }

    public function boot()
    {
        // Маршруты
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Представления
        $this->loadViewsFrom(__DIR__ . '/../Views', 'Captcha');

        // Публикация конфигов
        $this->publishes([
            __DIR__ . '/../Config/captcha.php' => config_path('captcha.php'),
        ], 'captcha-config');

        // Публикация ассетов (если нужны)
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('vendor/captcha'),
        ], 'captcha-assets');
    }
}
