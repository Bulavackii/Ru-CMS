<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PullWebmasterStats implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Что тянуть: пока только 'summary'; можно расширять ('hosts', 'links', …).
     */
    public function __construct(public string $section = 'summary') {}

    /** Повторные попытки и экспоненциальный бэкофф */
    public int $tries = 3;
    public function backoff(): array { return [60, 300, 900]; } // 1м, 5м, 15м

    /** Не запускать вторую такую же задачу в течение N сек. */
    public int $uniqueFor = 600; // 10 минут

    /** Уникальность зависит от host_id + section */
    public function uniqueId(): string
    {
        $hostId = (string) config('seo.webmaster.host_id', '');
        return 'webmaster:' . $hostId . ':' . $this->section;
    }

    public function handle(): void
    {
        if (!config('seo.features.webmaster')) {
            Log::info('PullWebmasterStats skipped: feature disabled');
            return;
        }

        $token   = (string) config('seo.webmaster.oauth_token', '');
        $hostId  = (string) config('seo.webmaster.host_id', '');
        $base    = rtrim((string) config('seo.webmaster.base', 'https://api.webmaster.yandex.net/v4'), '/');
        $timeout = (int) config('seo.webmaster.timeout', 10);

        if ($token === '' || $hostId === '') {
            Log::warning('PullWebmasterStats: not configured (missing token or host_id)');
            return;
        }

        $endpoint = match ($this->section) {
            'summary' => "{$base}/user/hosts/{$hostId}/summary",
            default   => "{$base}/user/hosts/{$hostId}/summary", // fallback
        };

        try {
            // Небольшой встроенный retry на сетевые мелочи (дополнительно к $tries/$backoff)
            $resp = Http::withHeaders(['Authorization' => 'OAuth '.$token])
                ->timeout($timeout)
                ->retry(2, 500) // 2 повтора с шагом 500мс
                ->get($endpoint);

            if ($resp->successful()) {
                $data = $resp->json() ?? [];

                // Сохраняем в кэш (для админки) и в файл (для отладки/резерва)
                $this->persist($hostId, $this->section, $data);

                Log::info('PullWebmasterStats: ok', [
                    'host_id' => $hostId,
                    'section' => $this->section,
                    'keys'    => is_array($data) ? array_slice(array_keys($data), 0, 10) : [],
                ]);
                return;
            }

            $status = $resp->status();
            if ($status === 429) {
                Log::warning('PullWebmasterStats: rate limited (429)', ['host_id' => $hostId, 'section' => $this->section]);
                return;
            }
            if ($status === 403) {
                Log::error('PullWebmasterStats: forbidden (403) — check OAuth token or permissions', ['host_id' => $hostId]);
                return;
            }
            if ($status === 404) {
                Log::error('PullWebmasterStats: host not found (404)', ['host_id' => $hostId]);
                return;
            }

            Log::error('PullWebmasterStats: API error', [
                'status'  => $status,
                'section' => $this->section,
                'body'    => $this->safeBody($resp->body()),
            ]);
        } catch (\Throwable $e) {
            Log::error('PullWebmasterStats: exception', [
                'message' => $e->getMessage(),
                'type'    => get_class($e),
                'section' => $this->section,
            ]);
            // Пробрасываем дальше, чтобы отработали tries/backoff очереди
            throw $e;
        }
    }

    /* ---------------- helpers ---------------- */

    protected function persist(string $hostId, string $section, mixed $data): void
    {
        // 1) Кэш на сутки (админка может показать быстро без файла/БД)
        $cacheKey = "seo:webmaster:{$hostId}:{$section}";
        Cache::put($cacheKey, $data, now()->addDay());

        // 2) JSON-файл для отладки/бэкапа
        $dir = storage_path('app/seo/webmaster');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . '/' . $hostId . '-' . $section . '.json';
        try {
            File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            @chmod($path, 0644);
        } catch (\Throwable $e) {
            Log::warning('PullWebmasterStats: could not write json', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    protected function safeBody(?string $body): string
    {
        $b = (string) $body;
        // ограничим размер в логах
        if (strlen($b) > 2000) {
            $b = substr($b, 0, 2000) . '…';
        }
        return $b;
    }
}
