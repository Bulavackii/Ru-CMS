<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

/**
 * 📊 Сервис мониторинга для РФ/СНГ
 * 
 * Альтернатива Sentry, адаптированная для российского рынка
 * Отправка уведомлений через Telegram, логирование в БД
 */
class MonitoringService
{
    protected string $telegramToken;
    protected string $telegramChatId;
    protected bool $enabled;

    public function __construct()
    {
        $this->telegramToken = config('services.telegram.token');
        $this->telegramChatId = config('services.telegram.chat_id');
        $this->enabled = config('monitoring.enabled', true);
    }

    /**
     * Отслеживание ошибки
     */
    public function trackError(\Throwable $exception, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $error = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Логирование в файл
        Log::error('Application Error', $error);

        // Сохранение в БД (если таблица существует)
        try {
            $this->saveToDatabase($error);
        } catch (\Exception $e) {
            // Игнорируем ошибки БД
        }

        // Отправка в Telegram для критических ошибок
        if ($this->isCritical($exception)) {
            $this->sendToTelegram($error);
        }

        // Кэширование для статистики
        $this->updateStats($error);
    }

    /**
     * Отслеживание производительности
     */
    public function trackPerformance(string $metric, float $value, array $tags = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = "monitoring:performance:{$metric}";
        
        // Сохранение метрики
        Cache::put("{$key}:last", $value, 3600);
        
        // Обновление статистики
        $stats = Cache::get("{$key}:stats", [
            'count' => 0,
            'sum' => 0,
            'min' => PHP_FLOAT_MAX,
            'max' => PHP_FLOAT_MIN,
        ]);

        $stats['count']++;
        $stats['sum'] += $value;
        $stats['min'] = min($stats['min'], $value);
        $stats['max'] = max($stats['max'], $value);
        $stats['avg'] = $stats['sum'] / $stats['count'];

        Cache::put("{$key}:stats", $stats, 3600);

        // Алерт при превышении лимита
        $limit = config("monitoring.limits.{$metric}", null);
        if ($limit && $value > $limit) {
            $this->sendAlert('performance', "Метрика {$metric} превысила лимит: {$value} > {$limit}", $tags);
        }
    }

    /**
     * Отправка алерта
     */
    public function sendAlert(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $alert = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::warning('Monitoring Alert', $alert);

        // Отправка в Telegram
        $this->sendToTelegram($alert, "🚨 Alert: {$message}");
    }

    /**
     * Получить статистику ошибок
     */
    public function getErrorStats(int $hours = 24): array
    {
        $key = "monitoring:errors:stats:{$hours}h";
        
        return Cache::remember($key, 300, function () use ($hours) {
            try {
                return DB::table('error_logs')
                    ->where('created_at', '>=', now()->subHours($hours))
                    ->selectRaw('
                        COUNT(*) as total,
                        COUNT(DISTINCT file) as unique_files,
                        COUNT(DISTINCT ip_address) as unique_ips,
                        AVG(created_at) as avg_time
                    ')
                    ->first();
            } catch (\Exception $e) {
                return [
                    'total' => 0,
                    'unique_files' => 0,
                    'unique_ips' => 0,
                ];
            }
        });
    }

    /**
     * Получить статистику производительности
     */
    public function getPerformanceStats(string $metric): array
    {
        $key = "monitoring:performance:{$metric}:stats";
        return Cache::get($key, []);
    }

    /**
     * Сохранение ошибки в БД
     */
    protected function saveToDatabase(array $error): void
    {
        try {
            DB::table('error_logs')->insert([
                'message' => substr($error['message'], 0, 500),
                'file' => $error['file'],
                'line' => $error['line'],
                'url' => $error['url'],
                'method' => $error['method'],
                'ip_address' => $error['ip'],
                'user_id' => $error['user_id'],
                'user_agent' => substr($error['user_agent'] ?? '', 0, 500),
                'context' => json_encode($error['context']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Таблица может не существовать
        }
    }

    /**
     * Отправка в Telegram
     */
    protected function sendToTelegram(array $data, ?string $customMessage = null): void
    {
        if (!$this->telegramToken || !$this->telegramChatId) {
            return;
        }

        try {
            $message = $customMessage ?? "🚨 Ошибка в приложении\n\n";
            $message .= "Сообщение: " . ($data['message'] ?? 'N/A') . "\n";
            $message .= "Файл: " . ($data['file'] ?? 'N/A') . ":" . ($data['line'] ?? 'N/A') . "\n";
            $message .= "URL: " . ($data['url'] ?? 'N/A') . "\n";
            $message .= "IP: " . ($data['ip'] ?? 'N/A') . "\n";
            $message .= "Время: " . ($data['timestamp'] ?? now()->toDateTimeString());

            Http::timeout(5)->post("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
                'chat_id' => $this->telegramChatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            // Игнорируем ошибки Telegram
            Log::warning('Failed to send Telegram notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Проверка, критична ли ошибка
     */
    protected function isCritical(\Throwable $exception): bool
    {
        // Критические типы ошибок
        $criticalTypes = [
            \Error::class,
            \ParseError::class,
            \TypeError::class,
            \PDOException::class,
        ];

        foreach ($criticalTypes as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }

        // Проверка по сообщению
        $criticalMessages = [
            'database',
            'connection',
            'memory',
            'timeout',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($criticalMessages as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Обновление статистики
     */
    protected function updateStats(array $error): void
    {
        $key = "monitoring:errors:24h";
        $count = Cache::increment($key);
        
        if ($count === 1) {
            Cache::put($key, 1, now()->addHours(24));
        }

        // Алерт при большом количестве ошибок
        if ($count >= 100) {
            $this->sendAlert('error_rate', "Высокий уровень ошибок: {$count} за 24 часа");
        }
    }
}

