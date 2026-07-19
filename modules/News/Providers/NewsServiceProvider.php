<?php

namespace Modules\News\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 📰 Сервис-провайдер модуля News
 */
class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = base_path('modules/News');

        // 🛣️ Подключение маршрутов
        if (file_exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        // 🖼️ Подключение представлений
        foreach (["$modulePath/Views", "$modulePath/Resources/views"] as $dir) {
            if (is_dir($dir)) {
                $this->loadViewsFrom($dir, 'News');
                break;
            }
        }

        // Миграции модуля живут в единой database/migrations/.

        // 🌐 Подключение переводов
        foreach (["$modulePath/Lang", "$modulePath/Resources/lang"] as $dir) {
            if (is_dir($dir)) {
                $this->loadTranslationsFrom($dir, 'News');
                break;
            }
        }
    }

    public function register(): void
    {
        //
    }
}

