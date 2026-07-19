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

class PullMetrica implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Параметры отчёта (можно переопределить при диспатче).
     * Примеры date: '7daysAgo' / 'today' / '2025-09-01'
     */
    public function __construct(
        public string $date1 = '7daysAgo',
        public string $date2 = 'today',
        public string $metrics = 'ym:s:visits,ym:s:pageviews,ym:s:users',
        public string $dimensions = 'ym:s:date',
        public ?int $limit = null,
        public ?int $offset = null,
        public ?string $filters = null,
        public array $extra = [] // attribution, accuracy, sort и т.п.
    ) {}

    /** Повторы и бэкофф: 3 попытки с паузами 1м / 5м / 15м */
    public int $tries = 3;
    public function backoff(): array { return [60, 300, 900]; }

    /** Не запускать дубликаты одной и той же задачи некоторое время */
    public int $uniqueFor = 600; // 10 минут

    /** Уникальность зависит от набора параметров и счётчика */
    public function uniqueId(): string
    {
        $counterId = (string) config('seo.metrica.counter_id', '');
        return 'metrica:' . md5(json_encode([
            'counter'    => $counterId,
            'date1'      => $this->date1,
            'date2'      => $this->date2,
            'metrics'    => $this->metrics,
            'dimensions' => $this->dimensions,
            'limit'      => $this->limit,
            'offset'     => $this->offset,
            'filters'    => $this->filters,
            'extra'      => $this->extra,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function handle(): void
    {
        if (!config('seo.features.metrica')) {
            Log::info('PullMetrica skipped: feature disabled');
            return;
        }

        $token     = (string) config('seo.metrica.oauth_token', '');
        $counterId = (string) config('seo.metrica.counter_id', '');
        $endpoint  = rtrim((string) config('seo.metrica.stats', 'https://api-metrika.yandex.net/stat/v1/data'), '/');
        $timeout   = (int) config('seo.metrica.timeout', 10);

        if ($token === '' || $counterId === '') {
            Log::warning('PullMetrica: not configured (missing token or counter_id)');
            return;
        }

        // Базовые параметры запроса
        $params = array_filter([
            'ids'        => $counterId,
            'metrics'    => $this->metrics,
            'dimensions' => $this->dimensions,
            'date1'      => $this->date1,
            'date2'      => $this->date2,
            'limit'      => $this->limit,
            'offset'     => $this->offset,
            'filters'    => $this->filters,
            'lang'       => 'ru',
            'pretty'     => 'false',
        ], static fn($v) => $v !== null && $v !== '');

        // Доп. параметры (accuracy, attribution, sort и т.п.)
        foreach ($this->extra as $k => $v) {
            if ($v !== null && $v !== '') $params[$k] = $v;
        }

        try {
            $resp = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $token,
                    'Accept'        => 'application/json',
                ])
                ->timeout($timeout)
                ->retry(2, 500) // 2 быстрых повтора по 500мс
                ->get($endpoint, $params);

            if ($resp->successful()) {
                $data = $resp->json() ?? [];

                // Готовим «упрощённый» вид для UI + сохраняем raw
                $prepared = $this->simplify($data);

                $this->persist(
                    $counterId,
                    [
                        'params'   => $params,
                        'fetched'  => now()->toIso8601String(),
                        'raw'      => $data,
                        'prepared' => $prepared,
                    ]
                );

                Log::info('PullMetrica: ok', [
                    'counter' => $counterId,
                    'rows'    => is_array($data['data'] ?? null) ? count($data['data']) : null,
                ]);
                return;
            }

            $status = $resp->status();
            if ($status === 429) {
                Log::warning('PullMetrica: rate limited (429)', ['counter' => $counterId]);
                return;
            }
            if ($status === 401 || $status === 403) {
                Log::error('PullMetrica: unauthorized/forbidden ('.$status.') — check OAuth token/permissions', ['counter' => $counterId]);
                return;
            }
            if ($status >= 500) {
                Log::warning('PullMetrica: server error', ['status' => $status]);
                // пробросим исключение, чтобы сработали tries/backoff очереди
                throw new \RuntimeException('Metrica server error '.$status);
            }

            Log::error('PullMetrica: API error', [
                'status' => $status,
                'body'   => $this->safeBody($resp->body()),
            ]);
        } catch (\Throwable $e) {
            Log::error('PullMetrica: exception', [
                'message' => $e->getMessage(),
                'type'    => get_class($e),
            ]);
            throw $e;
        }
    }

    /* ---------------- helpers ---------------- */

    /**
     * Преобразуем ответ Метрики в универсальный вид для графиков/таблиц.
     * Возвращаем:
     *  - metrics_labels: string[]
     *  - dimensions_labels: string[]
     *  - rows: array<int, array{dimensions: array, metrics: array}>
     */
    protected function simplify(array $data): array
    {
        $rows = [];
        $rawRows = is_array($data['data'] ?? null) ? $data['data'] : [];

        foreach ($rawRows as $r) {
            $dims = [];
            foreach (($r['dimensions'] ?? []) as $d) {
                $dims[] = $d['name'] ?? ($d['id'] ?? null);
            }
            $rows[] = [
                'dimensions' => $dims,
                'metrics'    => array_values($r['metrics'] ?? []),
            ];
        }

        $metricsLabels    = array_values(array_filter(array_map('trim', explode(',', $this->metrics)), fn($s) => $s !== ''));
        $dimensionsLabels = array_values(array_filter(array_map('trim', explode(',', $this->dimensions)), fn($s) => $s !== ''));

        return [
            'metrics_labels'    => $metricsLabels,
            'dimensions_labels' => $dimensionsLabels,
            'rows'              => $rows,
            // Для удобства: если единственная размерность — дата, добавим плоский timeseries
            'timeseries'        => $this->tryMakeTimeseries($rows, $dimensionsLabels, $metricsLabels),
        ];
    }

    /**
     * Если размерность только дата, вернём удобный timeseries:
     * [
     *   ['date' => '2025-09-10', 'ym:s:visits' => 123, 'ym:s:pageviews' => 456, ...],
     *   ...
     * ]
     */
    protected function tryMakeTimeseries(array $rows, array $dimLabels, array $metricLabels): ?array
    {
        if (count($dimLabels) !== 1 || ($dimLabels[0] ?? '') !== 'ym:s:date') {
            return null;
        }

        $out = [];
        foreach ($rows as $r) {
            $date = $r['dimensions'][0] ?? null;
            if (!$date) continue;

            $row = ['date' => $date];
            foreach ($metricLabels as $i => $m) {
                $row[$m] = (float) ($r['metrics'][$i] ?? 0);
            }
            $out[] = $row;
        }
        return $out;
    }

    protected function persist(string $counterId, array $payload): void
    {
        // Стабильный ключ кэша по уникальному id задачи
        $key = 'seo:metrica:' . $counterId . ':' . $this->uniqueId();
        Cache::put($key, $payload, now()->addHours(6));

        // И «последний отчёт» по этому счётчику (удобно для виджета)
        Cache::put('seo:metrica:last:' . $counterId, $payload, now()->addHours(6));

        // JSON-файл для отладки/резерва
        $dir = storage_path('app/seo/metrica');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $file = $dir . '/' . $counterId . '-' . substr(md5($this->uniqueId()), 0, 12) . '.json';
        try {
            File::put($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            @chmod($file, 0644);
        } catch (\Throwable $e) {
            Log::warning('PullMetrica: could not write json', ['path' => $file, 'error' => $e->getMessage()]);
        }
    }

    protected function safeBody(?string $body): string
    {
        $b = (string) $body;
        return strlen($b) > 2000 ? (substr($b, 0, 2000) . '…') : $b;
    }
}
