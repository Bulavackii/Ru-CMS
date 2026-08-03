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
        $cacheKey = 'news_list_v' . News::contentVersion() . '_page_' . request()->get('page', 1);
        
        $newsList = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function () {
            return News::with(['categories' => function ($q) {
                    // У категорий колонка называется title, а не name — из-за
                    // 'categories.name' весь список новостей отдавал 500
                    $q->select('categories.id', 'categories.title', 'categories.slug');
                }])
                // Поля шаблонов перечисляем поимённо: без rating плашка оценки
                // не появлялась у игровых карточек, без price/stock — цена у
                // товаров. Тот же промах уже был на главной.
                ->select('id', 'title', 'slug', 'content', 'template',
                    'price', 'stock', 'is_promo', 'rating', 'created_at', 'updated_at')
                ->published()
                ->orderByDesc('id')
                ->paginate(12);
        });

        // Адрес для ссылок страниц ставим ПОСЛЕ чтения из кеша.
        //
        // paginate() запоминает адрес того запроса, который наполнил кеш,
        // и дальше объект отдаётся всем как есть. Один заход с другого
        // хоста или порта — и ссылки страниц пятнадцать минут ведут не
        // туда: у владельца они вели на 127.0.0.1 без :8000, и страница
        // не открывалась вовсе.
        //
        // Версия содержимого в ключе — чтобы правка материала была видна
        // сразу, а не через четверть часа.
        $newsList = $newsList->withPath(request()->url());

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
