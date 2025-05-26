<?php

namespace Modules\Accessibility;

use Illuminate\Support\ServiceProvider;

class AccessibilityServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'Accessibility');
    }

    public function register() {}
}
