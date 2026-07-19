<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AnalyticsService;

/**
 * 📊 Middleware для отслеживания просмотров
 */
class TrackViews
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Отслеживать просмотры только для GET запросов и успешных ответов
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            // Получить модель из route параметров
            $model = $request->route('news') ?? $request->route('page') ?? $request->route('post');
            
            if ($model && method_exists($model, 'getMorphClass')) {
                $this->analytics->trackView($model, auth()->id());
            }
        }

        return $response;
    }
}

