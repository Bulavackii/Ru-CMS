<?php

namespace App\Services;

use App\Models\ContentView;
use App\Models\UniqueVisitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 📊 AnalyticsService - Расширенная аналитика
 * 
 * Обеспечивает:
 * - Отслеживание просмотров контента
 * - Интеграцию с Яндекс.Метрикой
 * - Статистику по популярному контенту
 * - Отчеты и графики
 */
class AnalyticsService
{
    private ?string $yandexApiKey = null;
    private ?string $yandexCounterId = null;

    public function __construct()
    {
        $settings = \DB::table('analytics_settings')->where('provider', 'yandex')->where('enabled', true)->first();
        if ($settings) {
            $this->yandexApiKey = $settings->api_key;
            $this->yandexCounterId = $settings->counter_id;
        }
    }

    /**
     * 📈 Записать просмотр контента
     */
    public function trackView($model, ?int $userId = null): void
    {
        try {
            ContentView::record($model, $userId);
            
            // Обновить счетчик уникальных посетителей
            UniqueVisitor::incrementViews(request()->ip(), request()->userAgent());
            
            // Обновить кешированную статистику
            $this->updateContentStatistics($model);
        } catch (\Exception $e) {
            Log::error('Analytics tracking failed', [
                'error' => $e->getMessage(),
                'model' => get_class($model),
            ]);
        }
    }

    /**
     * 📊 Получить статистику контента
     */
    public function getContentStats($model, ?\Carbon\Carbon $start = null, ?\Carbon\Carbon $end = null): array
    {
        $start = $start ?? now()->subMonth();
        $end = $end ?? now();

        $cacheKey = "content_stats:{$model->getMorphClass()}:{$model->id}:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($model, $start, $end) {
            $views = ContentView::where('model_type', get_class($model))
                ->where('model_id', $model->id)
                ->whereBetween('viewed_at', [$start, $end])
                ->get();

            return [
                'total_views' => $views->count(),
                'unique_views' => $views->unique('ip_address')->count(),
                'views_by_date' => $views->groupBy(function ($view) {
                    return $view->viewed_at->format('Y-m-d');
                })->map->count(),
            ];
        });
    }

    /**
     * 🔝 Получить популярный контент
     */
    public function getPopularContent(string $modelType, int $limit = 10, ?\Carbon\Carbon $start = null, ?\Carbon\Carbon $end = null): array
    {
        $start = $start ?? now()->subMonth();
        $end = $end ?? now();

        $cacheKey = "popular_content:{$modelType}:{$limit}:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($modelType, $limit, $start, $end) {
            return ContentStatistics::where('model_type', $modelType)
                ->whereBetween('period_start', [$start, $end])
                ->orderByDesc('views_count')
                ->limit($limit)
                ->get()
                ->map(function ($stat) {
                    $model = $stat->model_type::find($stat->model_id);
                    return [
                        'id' => $stat->model_id,
                        'title' => $model?->title ?? 'N/A',
                        'views' => $stat->views_count,
                        'unique_views' => $stat->unique_views,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * 📈 Получить статистику за период
     */
    public function getPeriodStats(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $cacheKey = "period_stats:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 1800, function () use ($start, $end) {
            $visitorStats = UniqueVisitor::getStats($start, $end);
            
            $contentViews = ContentView::whereBetween('viewed_at', [$start, $end])->get();
            
            return [
                'unique_visitors' => $visitorStats['unique_visitors'],
                'total_page_views' => $visitorStats['total_views'],
                'avg_session_duration' => round($visitorStats['avg_session_duration'] ?? 0),
                'content_views' => $contentViews->count(),
                'views_by_type' => $contentViews->groupBy('model_type')->map->count(),
                'top_referers' => $contentViews->whereNotNull('referer')
                    ->groupBy('referer')
                    ->map->count()
                    ->sortDesc()
                    ->take(10)
                    ->toArray(),
            ];
        });
    }

    /**
     * 🔄 Синхронизация с Яндекс.Метрикой
     */
    public function syncWithYandex(?\Carbon\Carbon $start = null, ?\Carbon\Carbon $end = null): array
    {
        if (!$this->yandexApiKey || !$this->yandexCounterId) {
            return ['error' => 'Yandex Metrika not configured'];
        }

        $start = $start ?? now()->subDay();
        $end = $end ?? now();

        try {
            $response = Http::withHeaders([
                'Authorization' => "OAuth {$this->yandexApiKey}",
            ])->get("https://api-metrika.yandex.net/stat/v1/data", [
                'ids' => $this->yandexCounterId,
                'metrics' => 'ym:s:visits,ym:s:pageviews,ym:s:users',
                'date1' => $start->format('Y-m-d'),
                'date2' => $end->format('Y-m-d'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'visits' => $data['data'][0]['metrics'][0] ?? 0,
                    'pageviews' => $data['data'][0]['metrics'][1] ?? 0,
                    'users' => $data['data'][0]['metrics'][2] ?? 0,
                ];
            }

            return ['error' => 'Failed to fetch data from Yandex Metrika'];
        } catch (\Exception $e) {
            Log::error('Yandex Metrika sync failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 📊 Обновить кешированную статистику контента
     */
    private function updateContentStatistics($model): void
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        ContentStatistics::updateOrCreate(
            [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'views_count' => \DB::raw('views_count + 1'),
            ]
        );
    }

    /**
     * 📈 Получить график просмотров за период
     */
    public function getViewsChart(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $views = ContentView::whereBetween('viewed_at', [$start, $end])
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $views->pluck('date')->toArray(),
            'data' => $views->pluck('count')->toArray(),
        ];
    }
}

