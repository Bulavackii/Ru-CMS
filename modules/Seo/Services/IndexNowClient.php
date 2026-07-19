<?php
namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class IndexNowClient
{
    /**
     * Отправляет URL-ы в IndexNow.
     *
     * @param  array<int,string> $urls  Абсолютные или относительные URL (лучше абсолютные).
     * @return array{
     *   ok: bool,                         // true, если все батчи успешно приняты
     *   total:int, sent:int,              // всего валидных и реально отправленных URL
     *   batches:int,                      // количество HTTP-запросов
     *   successes:int, failures:int,      // счётчики по батчам
     *   last_status:int|string,           // HTTP-код последнего ответа или строковый код
     *   skipped_foreign_hosts:int,        // отфильтровано из-за чужого хоста
     *   details: array<int, array{status:int|string, ok:bool, count:int, body:mixed}> // по каждому батчу
     * }
     */
    public function submit(array $urls): array
    {
        // Фич-флаг: можно мгновенно отключить
        if (!config('seo.features.indexnow', false)) {
            return [
                'ok' => false, 'total' => 0, 'sent' => 0,
                'batches' => 0, 'successes' => 0, 'failures' => 0,
                'last_status' => 'disabled',
                'skipped_foreign_hosts' => 0,
                'details' => [],
            ];
        }

        $gateway = (string) config('seo.indexnow.host', 'https://api.indexnow.org/indexnow');
        $key     = trim((string) config('seo.indexnow.key', ''));
        $timeout = (int) config('seo.indexnow.timeout', 5);
        $maxPer  = (int) config('seo.indexnow.max_per_request', 1000);
        $ensureKeyFile = (bool) config('seo.indexnow.ensure_key_file', true);

        if ($key === '') {
            return [
                'ok' => false, 'total' => 0, 'sent' => 0,
                'batches' => 0, 'successes' => 0, 'failures' => 0,
                'last_status' => 'no-key',
                'skipped_foreign_hosts' => 0,
                'details' => ['msg' => 'INDEXNOW_KEY is not set'],
            ];
        }

        // Базовый хост из app.url (иначе — из запроса)
        $appUrl = rtrim((string) config('app.url'), '/');
        $siteBase = $appUrl !== '' ? $appUrl : rtrim(request()->getSchemeAndHttpHost(), '/');
        $host = (string) parse_url($siteBase, PHP_URL_HOST);

        // Имя файла ключа — только basename
        $cfgKeyFile = config('seo.indexnow.key_filename');
        $keyFile    = $cfgKeyFile ? basename((string) $cfgKeyFile) : ($key . '.txt');

        // При желании — гарантируем наличие файла ключа в корне сайта
        if ($ensureKeyFile) {
            try {
                $publicPath = public_path($keyFile);
                if (!File::exists($publicPath) || trim((string) @File::get($publicPath)) !== $key) {
                    // создаём/перезаписываем; минимальные права
                    File::put($publicPath, $key);
                    @chmod($publicPath, 0644);
                }
            } catch (\Throwable $e) {
                Log::warning('IndexNow: cannot ensure key file', ['file' => $keyFile, 'error' => $e->getMessage()]);
            }
        }

        $keyLocation = $siteBase . '/' . ltrim($keyFile, '/');

        // Подготовка URL: трим, абсолютим, валидируем, дедуп
        $prepared = [];
        foreach ($urls as $u) {
            if (!is_string($u)) continue;
            $u = trim($u);
            if ($u === '') continue;

            if (!preg_match('~^https?://~i', $u)) {
                $u = $siteBase . '/' . ltrim($u, '/');
            }
            if (!filter_var($u, FILTER_VALIDATE_URL)) continue;

            // IndexNow заявляет host — домен сайта. Фильтруем чужие домены.
            $h = (string) parse_url($u, PHP_URL_HOST);
            if ($h !== $host) {
                // Можно разрешить отправку на чужие хосты флагом:
                if (!config('seo.indexnow.allow_foreign_hosts', false)) {
                    $prepared['__skip__:' . $u] = '__foreign__';
                    continue;
                }
            }

            $prepared[$u] = true;
        }

        // Разделим на «валидные» и «отфильтрованные по чужому хосту»
        $validUrls = [];
        $skippedForeign = 0;
        foreach ($prepared as $u => $v) {
            if ($v === '__foreign__') { $skippedForeign++; continue; }
            $validUrls[] = $u;
        }

        $total = count($validUrls);
        if ($total === 0) {
            return [
                'ok' => false, 'total' => 0, 'sent' => 0,
                'batches' => 0, 'successes' => 0, 'failures' => 0,
                'last_status' => 'no-urls',
                'skipped_foreign_hosts' => $skippedForeign,
                'details' => ['msg' => 'No valid URLs to submit'],
            ];
        }

        // Чанкуем запросы (на всякий случай, чтобы не упереться в размер payload)
        $chunks = array_chunk($validUrls, max(1, $maxPer));
        $details   = [];
        $successes = 0;
        $failures  = 0;
        $lastStatus = null;
        $sent = 0;

        foreach ($chunks as $chunk) {
            $payload = [
                'host'        => $host,
                'key'         => $key,
                'keyLocation' => $keyLocation,
                'urlList'     => array_values($chunk),
            ];

            try {
                $resp = Http::timeout($timeout)
                    ->withHeaders([
                        // помогает отладке у провайдера
                        'User-Agent' => 'IndexNowClient/1.0 (+'. $siteBase .')',
                    ])
                    ->asJson()
                    ->post($gateway, $payload);

                $lastStatus = $resp->status();
                $ct   = strtolower((string) $resp->header('Content-Type', ''));
                $body = str_contains($ct, 'application/json')
                    ? ($resp->json() ?? $resp->body())
                    : $resp->body();

                $ok = $resp->successful();
                $ok ? $successes++ : $failures++;
                $sent += count($chunk);

                $details[] = [
                    'ok'     => $ok,
                    'status' => $lastStatus,
                    'count'  => count($chunk),
                    'body'   => $body,
                ];
            } catch (\Throwable $e) {
                $lastStatus = 'exception';
                $failures++;
                Log::error('IndexNow submit failed', [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                ]);
                $details[] = [
                    'ok'     => false,
                    'status' => 'exception',
                    'count'  => count($chunk),
                    'body'   => $e->getMessage(),
                ];
            }
        }

        return [
            'ok' => $failures === 0,
            'total' => $total,
            'sent' => $sent,
            'batches' => count($chunks),
            'successes' => $successes,
            'failures' => $failures,
            'last_status' => $lastStatus ?? 'unknown',
            'skipped_foreign_hosts' => $skippedForeign,
            'details' => $details,
        ];
    }
}
