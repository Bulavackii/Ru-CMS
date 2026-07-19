<?php

namespace Modules\Search\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;

class SearchController extends Controller
{
    /**
     * 🔍 Обработка запроса поиска на клиентской части сайта
     */
    public function index(Request $request)
    {
        // 📥 Получаем и валидируем поисковый запрос
        $request->validate([
            'q' => 'nullable|string|max:255|min:2',
        ]);

        $query = trim($request->input('q', ''));

        // Если запрос слишком короткий или пустой, возвращаем пустые результаты
        if (strlen($query) < 2) {
            return view('Search::frontend.index', [
                'query' => $query,
                'news' => collect(),
                'products' => collect(),
                'totalCount' => 0,
            ]);
        }

        // 📰 Поиск по новостям (опубликованным)
        $news = News::where('published', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('meta_description', 'like', "%{$query}%")
                  ->orWhere('meta_keywords', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'news_page');

        // 🛒 Поиск по товарам (News с template = products)
        $products = News::where('published', true)
            ->where('template', 'products')
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('meta_description', 'like', "%{$query}%")
                  ->orWhere('meta_keywords', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'products_page');

        $totalCount = $news->total() + $products->total();

        // 📄 Возвращаем результат в Blade-шаблон с переменными
        return view('Search::frontend.index', compact('query', 'news', 'products', 'totalCount'));
    }

    /**
     * 🔍 AJAX автодополнение поисковых запросов
     */
    public function autocomplete(Request $request)
    {
        $query = trim($request->input('q', ''));
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Кэшируем популярные запросы
        $cacheKey = 'search_autocomplete_' . md5($query);
        $results = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($query) {
            $suggestions = [];

            // Предложения из заголовков новостей
            $newsTitles = News::where('published', true)
                ->where('title', 'like', "%{$query}%")
                ->limit(5)
                ->pluck('title')
                ->map(fn($title) => ['text' => $title, 'type' => 'news']);

            // Предложения из товаров
            $productTitles = News::where('published', true)
                ->where('template', 'products')
                ->where('title', 'like', "%{$query}%")
                ->limit(5)
                ->pluck('title')
                ->map(fn($title) => ['text' => $title, 'type' => 'product']);

            return $newsTitles->merge($productTitles)->take(8)->values();
        });

        return response()->json($results);
    }
}
