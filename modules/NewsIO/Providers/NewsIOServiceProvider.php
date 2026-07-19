<?php

namespace Modules\NewsIO\Providers;

use Illuminate\Support\ServiceProvider;

class NewsIOServiceProvider extends ServiceProvider
{
    public function register(){}

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'NewsIO');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\NewsIO\Console\ExportNews::class,
                \Modules\NewsIO\Console\ImportNews::class,
            ]);
        }
    }
}
