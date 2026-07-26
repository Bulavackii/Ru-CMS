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
    }

    public function register(): void
    {
        //
    }
}
