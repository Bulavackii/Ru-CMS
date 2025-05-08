<?php

namespace Modules\News\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Миграции
        $this->loadMigrationsFrom(__DIR__ . '/../modules/News/Migrations');

        // 👇 Регистрация Blade-компонента
        Blade::componentNamespace('Modules\\News\\Views\\Components', 'news');

        // 👇 Альтернативный способ для конкретного компонента
        Blade::component('News::admin.template-badge', 'template-badge');
    }
}
