<?php

namespace Modules\News\Controllers\Frontend;

use App\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use Modules\News\Models\News;

class NewsController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index()
    {
        // Кэшируем список новостей на 15 минут
        $cacheKey = 'news_list_page_' . request()->get('page', 1);
        
        $newsList = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function () {
            return News::with(['categories' => function ($q) {
                    $q->select('categories.id', 'categories.name', 'categories.slug');
                }])
                ->select('id', 'title', 'slug', 'content', 'template', 'created_at', 'updated_at')
                ->published()
                ->orderByDesc('id')
                ->paginate(10);
        });

        return view('frontend.news.index', compact('newsList'));
    }

    public function show($slug)
    {
        $news = News::with('categories')
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Отслеживание просмотра
        $this->analytics->trackView($news, auth()->id());

        return view('frontend.news.show', [
            'news' => $news,
            'meta_title' => $news->meta_title ?? $news->title,
            'meta_description' => $news->meta_description,
            'meta_keywords' => $news->meta_keywords,
            'title' => $news->title, // для <title> в Blade
        ]);
    }
}
