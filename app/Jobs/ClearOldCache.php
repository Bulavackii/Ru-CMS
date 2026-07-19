<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearOldCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    public function handle(): void
    {
        Log::info('Cache cleanup started');

        $cleared = 0;
        $errors = 0;

        // Очистка старых кэшей по тегам
        $tags = ['home', 'news', 'theme', 'modules'];

        foreach ($tags as $tag) {
            try {
                Cache::tags([$tag])->flush();
                $cleared++;
                Log::info("Cache tag cleared", ['tag' => $tag]);
            } catch (\Throwable $e) {
                $errors++;
                Log::error("Failed to clear cache tag", [
                    'tag' => $tag,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Очистка специфических ключей старше 24 часов
        $this->clearExpiredKeys();

        Log::info('Cache cleanup completed', [
            'cleared_tags' => $cleared,
            'errors' => $errors,
        ]);

        // Отчет в Telegram
        if (app()->environment('production')) {
            $this->sendReport($cleared, $errors);
        }
    }

    private function clearExpiredKeys(): void
    {
        // Получаем все ключи из Redis (если используется)
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Cache::getStore()->getRedis()->keys('laravel_*');

            foreach ($keys as $key) {
                // Удаляем ключи старше 24 часов
                $ttl = Cache::getStore()->getRedis()->ttl($key);
                if ($ttl === -1 || $ttl > 86400) {
                    Cache::forget(str_replace('laravel_', '', $key));
                }
            }
        }
    }

    private function sendReport(int $cleared, int $errors): void
    {
        $message = "🧹 Очистка кэша завершена\n";
        $message .= "✅ Очищено тегов: {$cleared}\n";
        $message .= "❌ Ошибок: {$errors}\n";
        $message .= "📅 Дата: " . now()->format('Y-m-d H:i:s');

        try {
            $token = config('services.telegram.token');
            $chatId = config('services.telegram.chat_id');

            if ($token && $chatId) {
                file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
                    'chat_id' => $chatId,
                    'text' => $message,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram report', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('Cache cleanup failed permanently', [
            'error' => $exception->getMessage(),
        ]);

        // Уведомление администратора
        if (app()->environment('production')) {
            $token = config('services.telegram.token');
            $chatId = config('services.telegram.chat_id');

            if ($token && $chatId) {
                $message = "❌ Ошибка очистки кэша\n";
                $message .= "❗️ " . $exception->getMessage();

                file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
                    'chat_id' => $chatId,
                    'text' => $message,
                ]));
            }
        }
    }
}
