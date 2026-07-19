<?php

namespace Modules\Messages\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 📦 Сервис-провайдер модуля "Messages"
 *
 * Отвечает за:
 * 🔹 Подключение маршрутов
 * 🔹 Загрузку представлений
 */
class MessagesServiceProvider extends ServiceProvider
{
    /**
     * 🛠️ Метод boot()
     *
     * Загружает представления и маршруты модуля.
     */
    public function boot(): void
    {
        // 🖼️ Подключение представлений из директории модуля
        $this->loadViewsFrom(module_path('Messages', 'Resources/views'), 'messages');

        // 🧭 Подключение маршрутов модуля
        $this->loadRoutesFrom(module_path('Messages', 'Routes/web.php'));
    }
}
