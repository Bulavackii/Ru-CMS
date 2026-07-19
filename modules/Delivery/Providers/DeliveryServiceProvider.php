<?php

namespace Modules\Delivery\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Delivery\Services\DeliveryCalculatorService;

/**
 * 🚚 Сервис-провайдер модуля Delivery
 *
 * Загружает:
 * - маршруты 🛣️
 * - представления 👁️
 * - миграции 🧩
 * - сервисы 🔧
 */
class DeliveryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 🛣️ Подключаем маршруты модуля
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // 👁️ Указываем расположение представлений модуля
        $this->loadViewsFrom(__DIR__ . '/../Views', 'Delivery');

        // Миграции модуля живут в единой database/migrations/.
    }

    public function register()
    {
        // Регистрируем сервис расчета доставки
        $this->app->singleton(DeliveryCalculatorService::class);
    }
}
