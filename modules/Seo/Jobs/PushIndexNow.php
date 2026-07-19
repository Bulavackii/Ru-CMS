<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Modules\Seo\Services\IndexNowClient;

/**
 * Отправка URL-ов в IndexNow.
 * - безопасно пропускается, если фича выключена или нет ключа
 * - не падает, если нет прав на запись ключевого файла — просто логирует
 * - дедуплицирует URL-ы, нормализует до абсолютных
 * - бьёт на батчи (config seo.indexnow.batch)
 * - уникализируется на N секунд, чтобы не спамить одинаковыми задачами
 */
class PushIndexNow implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Повторные попытки и бэкофф на случай 5xx/сбоев сети */
    public int $tries = 3;
    public function backoff(): array { return [60, 300, 900]; } // 1м, 5м, 15м

    /** Сколько секунд задача с одинаковым uniqueId считается дубликатом */
    public int $uniqueFor = 300; // 5 минут

    /**
     * @param array<int,string> $urls Ссылки (могут быть относительные) на созданные/обновлённые страницы.
     */
    public function __construct(public array $urls) {}

    /** Уникальность: хэшируем нормализованный список URL-ов */
    public function uniqueId(): string
    {
        $base = $this->resolveBaseUrl();
        $norm = [];
        foreach ($this->urls as $u) {
            $u = is_string($u) ? trim($u) : '';
            if ($u === '') continue;
            $norm[] = $this->toAbsoluteUrl($u, $base);
        }
        sort($norm, SORT_STRING);
        return 'indexnow:' . md5(implode('|', $norm));
    }

    public function handle(): void
    {
        if (!config('seo.features.indexnow')) {
            Log::info('PushIndexNow skipped: feature disabled');
            return;
        }

        $key = (string) config('seo.indexnow.key', '');
        if ($key === '') {
            Log::warning('PushIndexNow skipped: INDEXNOW_KEY is not set');
            return;
        }

        $base = $this->resolveBaseUrl();
        if ($base === '') {
            Log::warning('PushIndexNow skipped: app.url is empty');
            return;
        }

        // Обеспечиваем ключевой файл <key>.txt в корне сайта
        $this->ensureKeyFile($key);

        // Нормализуем URL-ы: приводим к абсолютным, валидируем, дедупим
        $prepared = [];
        foreach ($this->urls as $u) {
            $u = is_string($u) ? trim($u) : '';
            if ($u === '') continue;

            $abs = $this->toAbsoluteUrl($u, $base);
            if ($abs === '' || !filter_var($abs, FILTER_VALIDATE_URL)) continue;

            $prepared[$abs] = true; // set
        }
        $urls = array_keys($prepared);

        if (empty($urls)) {
            Log::info('PushIndexNow skipped: no valid urls');
            return;
        }

        $batchSize = (int) config('seo.indexnow.batch', 1000);
        $client    = new IndexNowClient();

        foreach (array_chunk($urls, max(1, $batchSize)) as $i => $chunk) {
            try {
                $res = $client->submit($chunk); // ['ok'=>bool,'status'=>int|string,'body'=>mixed]
                $ok  = is_array($res) ? (bool)($res['ok'] ?? false) : false;

                if ($ok) {
                    Log::info('PushIndexNow: batch OK', [
                        'batch'  => $i + 1,
                        'count'  => count($chunk),
                        'status' => $res['status'] ?? null,
                    ]);
                } else {
                    Log::warning('PushIndexNow: batch failed', [
                        'batch'  => $i + 1,
                        'count'  => count($chunk),
                        'status' => $res['status'] ?? null,
                        'body'   => $res['body'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('PushIndexNow: exception on batch', [
                    'batch'   => $i + 1,
                    'count'   => count($chunk),
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                ]);
                // Дадим очереди повторить с backoff
                throw $e;
            }

            // лёгкая пауза между батчами
            usleep(200 * 1000); // 200ms
        }
    }

    /* -------------------- helpers -------------------- */

    protected function resolveBaseUrl(): string
    {
        // В задачах очереди request() часто отсутствует — опираемся на app.url
        $base = rtrim((string) config('app.url'), '/');
        return $base;
    }

    protected function toAbsoluteUrl(string $u, string $base): string
    {
        if ($u === '') return '';
        if (preg_match('~^https?://~i', $u)) return $u;
        if ($base === '') return '';
        return $base . '/' . ltrim($u, '/');
    }

    protected function ensureKeyFile(string $key): void
    {
        try {
            $cfgName   = config('seo.indexnow.key_filename');
            $fileName  = $cfgName ? basename((string)$cfgName) : ($key . '.txt');
            $keyPath   = public_path($fileName);
            $needWrite = true;

            if (File::exists($keyPath)) {
                $curr = @File::get($keyPath);
                $needWrite = trim((string)$curr) !== $key;
            }

            if ($needWrite) {
                File::put($keyPath, $key);
                @chmod($keyPath, 0644);
                Log::info('PushIndexNow: key file ensured', ['path' => $keyPath]);
            }
        } catch (\Throwable $e) {
            Log::error('PushIndexNow: failed to ensure key file', ['message' => $e->getMessage()]);
            // не прерываем выполнение — пинг всё равно попробуем
        }
    }
}
