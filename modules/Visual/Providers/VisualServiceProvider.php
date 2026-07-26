<?php

namespace Modules\Visual\Providers;

use Illuminate\Support\ServiceProvider;

class VisualServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'Visual');
        // Миграции модуля живут в единой database/migrations/.

        // 🌱 Консольные команды модуля
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Visual\Console\Commands\SeedDefaultThemesCommand::class,
            ]);
        }

        // NB: тема посетителя и список тем для шапки раздаются глобальным
        // App\Providers\ThemeServiceProvider, а не отсюда: этот провайдер
        // грузится только когда модуль Visual активен, а оформление сайта
        // должно работать в любом случае.
    }

    public function register(): void
    {
        //
    }
}
