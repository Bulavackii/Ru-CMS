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

        // Правило валидации.
        //
        // 'captcha' без параметра — новый способ: тип берётся из самого
        // выданного экземпляра по captcha_id, который приходит скрытым полем
        // вместе с формой. Это единственный способ проверить ДВЕ каптчи на
        // одной странице и не перепутать их между собой.
        //
        // 'captcha:image' со старым параметром работает как раньше: сверяется
        // самый свежий экземпляр указанного типа. На этой форме держатся
        // модуль Комментариев и уже написанные пользователем формы.
        Validator::extend('captcha', function ($attribute, $value, $parameters, $validator) {
            $service = app('captcha');
            $data = $validator->getData();
            $instanceId = $data['captcha_id'] ?? null;

            if (empty($parameters) && is_string($instanceId) && $instanceId !== '') {
                return $service->verifyInstance((string) $value, $instanceId);
            }

            return $service->verify((string) $value, $parameters[0] ?? 'image', is_string($instanceId) ? $instanceId : null);
        });

        Validator::replacer('captcha', fn () => 'Проверочный код введён неверно.');
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Captcha\Console\Commands\SeedDefaultCaptchaPresetsCommand::class,
            ]);
        }
    }
}
