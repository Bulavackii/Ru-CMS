<?php

namespace Modules\Install;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class InstallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $base = __DIR__;

        // 📦 Маршруты инсталлятора
        if (file_exists($base . '/Routes/web.php')) {
            try {
                $this->loadRoutesFrom($base . '/Routes/web.php');
                Log::info('InstallServiceProvider: Routes loaded successfully');
            } catch (\Throwable $e) {
                Log::error('InstallServiceProvider: Failed to load routes', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning('InstallServiceProvider: Routes file not found', [
                'path' => $base . '/Routes/web.php'
            ]);
        }

        // 🖼 Представления (Blade)
        if (is_dir($base . '/Views')) {
            $this->loadViewsFrom($base . '/Views', 'Install');
        }

        // Миграции проекта (в т.ч. модульные) живут в единой
        // database/migrations/ и подхватываются Laravel автоматически.
    }
}
