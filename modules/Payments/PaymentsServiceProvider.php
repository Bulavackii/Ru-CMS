<?php

namespace Modules\Payments;

use Illuminate\Support\ServiceProvider;

/**
 * 💳 Сервис-провайдер модуля Payments
 *
 * Загружает:
 * - маршруты 🛣️
 * - представления 👁️
 * - миграции 🧩
 */
class PaymentsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 🛣️ Подключаем маршруты модуля
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        // 👁️ Указываем расположение представлений модуля
        $this->loadViewsFrom(__DIR__ . '/Views', 'Payments');

        // Миграции модуля живут в единой database/migrations/.
    }
}
