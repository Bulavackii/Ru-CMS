<?php

namespace Modules\Messages\Providers;

use Illuminate\Support\ServiceProvider;

class MessagesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(module_path('Messages', 'Resources/views'), 'messages');
        $this->loadRoutesFrom(module_path('Messages', 'Routes/web.php'));
    }
}
