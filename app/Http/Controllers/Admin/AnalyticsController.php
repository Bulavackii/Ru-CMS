<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * 📊 Главная страница аналитики
     */
    public function index(Request $request): View
    {
        $period = $request->input('period', 'month'); // day, week, month, year
        
        $start = match($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
        $end = now();

        $stats = $this->analytics->getPeriodStats($start, $end);
        $viewsChart = $this->analytics->getViewsChart($start, $end);
        $popularNews = $this->analytics->getPopularContent('Modules\News\Models\News', 10, $start, $end);
        $popularPages = $this->analytics->getPopularContent('Modules\Menu\Models\Page', 10, $start, $end);

        // Синхронизация с Яндекс.Метрикой (если настроена)
        $yandexData = $this->analytics->syncWithYandex($start, $end);

        return view('admin.analytics.index', compact(
            'stats',
            'viewsChart',
            'popularNews',
            'popularPages',
            'yandexData',
            'period'
        ));
    }

    /**
     * 📈 API для получения статистики
     */
    public function getStats(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->input('start', now()->subMonth()));
        $end = Carbon::parse($request->input('end', now()));

        $stats = $this->analytics->getPeriodStats($start, $end);
        $viewsChart = $this->analytics->getViewsChart($start, $end);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'chart' => $viewsChart,
        ]);
    }

    /**
     * 🔝 Популярный контент
     */
    public function popularContent(Request $request): JsonResponse
    {
        $type = $request->input('type', 'news');
        $limit = $request->input('limit', 10);
        $start = Carbon::parse($request->input('start', now()->subMonth()));
        $end = Carbon::parse($request->input('end', now()));

        $modelType = match($type) {
            'news' => 'Modules\News\Models\News',
            'pages' => 'Modules\Menu\Models\Page',
            default => 'Modules\News\Models\News',
        };

        $content = $this->analytics->getPopularContent($modelType, $limit, $start, $end);

        return response()->json([
            'success' => true,
            'content' => $content,
        ]);
    }

    /**
     * ⚙️ Настройки аналитики
     */
    public function settings(): View
    {
        $settings = \DB::table('analytics_settings')->where('provider', 'yandex')->first();

        return view('admin.analytics.settings', compact('settings'));
    }

    /**
     * 💾 Сохранить настройки
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'counter_id' => 'nullable|string',
            'enabled' => 'boolean',
        ]);

        \DB::table('analytics_settings')->updateOrInsert(
            ['provider' => 'yandex'],
            [
                'api_key' => $validated['api_key'] ?? null,
                'counter_id' => $validated['counter_id'] ?? null,
                'enabled' => $validated['enabled'] ?? false,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Настройки сохранены',
        ]);
    }
}

