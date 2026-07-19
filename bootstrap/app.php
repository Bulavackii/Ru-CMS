<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

$envPath = __DIR__ . '/../.env';
$envExamplePath = __DIR__ . '/../.env.example';

if (!file_exists($envPath)) {
    if (file_exists($envExamplePath)) {
        copy($envExamplePath, $envPath);
    } else {
        // Создаем минимальный .env файл если .env.example отсутствует
        $minimalEnv = "APP_NAME=\"RU CMS\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\n";
        file_put_contents($envPath, $minimalEnv);
    }
}

// Проверяем существование файла перед чтением
if (!file_exists($envPath)) {
    throw new \RuntimeException('.env file not found and could not be created');
}

$env = file_get_contents($envPath);

// 👇 Проверка: нет строки, пустая или явно placeholder
if (
    !preg_match('/^APP_KEY=.*$/m', $env) ||
    preg_match('/^APP_KEY=(null|placeholder)?$/m', $env)
) {
    $key = 'base64:' . base64_encode(random_bytes(32));

    if (preg_match('/^APP_KEY=.*$/m', $env)) {
        $env = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $env);
    } else {
        $env .= "\nAPP_KEY={$key}";
    }

    file_put_contents($envPath, $env);
}

/**
 * 🚀 Инициализация Laravel-приложения (Laravel 12)
 *
 * Здесь настраиваются:
 * - базовый путь
 * - маршруты
 * - консольные команды
 * - middleware-алиасы
 * - обработка исключений
 * - регистрация сервис-провайдеров
 */

// Критичные модули, которые должны загружаться вручную (до инициализации БД)
$criticalModules = [];
if (class_exists(\Modules\System\Providers\SystemServiceProvider::class)) {
    $criticalModules[] = \Modules\System\Providers\SystemServiceProvider::class;
}
if (class_exists(\Modules\Install\InstallServiceProvider::class)) {
    $criticalModules[] = \Modules\Install\InstallServiceProvider::class;
}

// Загружаем стандартные провайдеры и добавляем критичные модули
$providers = array_merge(
    require __DIR__.'/providers.php',
    $criticalModules
);

return Application::configure(basePath: dirname(__DIR__))

    // 🧩 Регистрация провайдеров (включая критичные модули)
    ->withProviders($providers)

    // 🔁 Маршруты: web, console, health-check
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )

    // 🛡️ Middleware-алиасы (короткие имена)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'skip.install.db' => \App\Http\Middleware\SkipDatabaseForInstall::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
            'localization' => \App\Http\Middleware\LocalizationMiddleware::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimit::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'redirect.if.not.installed' => \App\Http\Middleware\RedirectIfNotInstalled::class,
            'block.if.installed' => \App\Http\Middleware\BlockIfInstalled::class,
        ]);

        // Глобальный middleware для безопасности (кроме установки)
        // SkipDatabaseForInstall должен выполняться ПЕРВЫМ, чтобы переключить драйвер сессий до StartSession
        $middleware->web(prepend: [
            \App\Http\Middleware\SkipDatabaseForInstall::class,
            \App\Http\Middleware\RedirectIfNotInstalled::class,
        ]);
        
        // Middleware безопасности (исключая маршруты установки)
        $middleware->web(append: [
            \App\Http\Middleware\SecurityMiddleware::class,
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\AuditLog::class,
        ]);
    })

    // ⚠️ Обработка исключений (настраивается при необходимости)
    ->withExceptions(function (Exceptions $exceptions) {
        // Можно добавить кастомные обработчики, логирование и т.п.
    })

    // 📅 Расписание задач (schedule)
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Генерация sitemap каждый день в 3:00 ночи
        $schedule->job(new \App\Jobs\GenerateSitemap())
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule.log'));

        // Очистка кэша каждую неделю в понедельник в 4:00
        $schedule->job(new \App\Jobs\ClearOldCache())
            ->weekly()
            ->at('04:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule.log'));

        // Резервное копирование БД
        $dbSchedule = config('backup.schedule.database', 'daily');
        $dbJob = $schedule->job(new \App\Jobs\BackupDatabase());
        match($dbSchedule) {
            'daily' => $dbJob->daily(),
            'weekly' => $dbJob->weekly(),
            'monthly' => $dbJob->monthly(),
            default => $dbJob->daily(),
        };
        $dbJob->at('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/backup.log'))
            ->emailOutputOnFailure(config('backup.notification_email'));

        // Резервное копирование файлов
        $filesSchedule = config('backup.schedule.files', 'weekly');
        $filesJob = $schedule->job(new \App\Jobs\BackupFiles());
        match($filesSchedule) {
            'daily' => $filesJob->daily(),
            'weekly' => $filesJob->weekly(),
            'monthly' => $filesJob->monthly(),
            default => $filesJob->weekly(),
        };
        $filesJob->at('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/backup.log'))
            ->emailOutputOnFailure(config('backup.notification_email'));

        // Очистка старых сессий каждый день
        $schedule->command('session:gc')
            ->daily()
            ->withoutOverlapping();

        // Проверка истечения лицензии каждый день в 9:00
        $schedule->job(new \App\Jobs\CheckLicenseExpiration())
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/license.log'));
    })

    // 🧱 Создание приложения (возвращает Application)
    ->create();

// 🧩 Регистрация модульных провайдеров (некритичные модули)
// Критичные модули уже зарегистрированы через withProviders() выше
try {
    $app = app();

    // Автоматическая регистрация модулей из module.json файлов
    $modulesPath = base_path('modules');
    if (is_dir($modulesPath)) {
        $moduleDirs = array_filter(glob($modulesPath . '/*'), function($path) {
            return is_dir($path);
        });
        $modules = [];

        foreach ($moduleDirs as $moduleDir) {
            $moduleJsonPath = $moduleDir . '/module.json';
            if (file_exists($moduleJsonPath)) {
                try {
                    $moduleData = json_decode(file_get_contents($moduleJsonPath), true);
                    if ($moduleData && isset($moduleData['providers']) && is_array($moduleData['providers'])) {
                        $priority = $moduleData['priority'] ?? 50;
                        $active = $moduleData['active'] ?? true;

                        if ($active) {
                            foreach ($moduleData['providers'] as $providerClass) {
                                $modules[] = [
                                    'provider' => $providerClass,
                                    'priority' => $priority,
                                    'name' => $moduleData['name'] ?? basename($moduleDir),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Failed to parse module.json: {$moduleJsonPath}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Сортировка по приоритету
        usort($modules, fn($a, $b) => $a['priority'] <=> $b['priority']);

        // Регистрация модулей
        foreach ($modules as $module) {
            try {
                if (class_exists($module['provider'])) {
                    $app->register($module['provider']);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to register module: {$module['name']}", [
                    'provider' => $module['provider'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} catch (\Throwable $e) {
    // Логируем ошибку, но не прерываем загрузку приложения
    Log::error('Module registration failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
