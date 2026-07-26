<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;

/**
 * 🔍 FrontendSearchController
 *
 * Поиск по публичной части сайта: маршрут /search (frontend.search),
 * форма из шапки сайта шлёт запросы сюда.
 *
 * Ищет по опубликованным новостям и страницам. Страницы показываются
 * отдельным компактным блоком (их обычно единицы), новости — основным
 * списком с пагинацией.
 */
class FrontendSearchController extends Controller
{
    /** Сколько страниц показываем над списком новостей */
    private const PAGES_LIMIT = 5;

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = trim((string) $request->input('q', ''));

        $results = News::query()->whereRaw('1 = 0')->paginate(12); // пустой пагинатор
        $pages = collect();

        if (mb_strlen($query) >= 2) {
            // search_like(): ILIKE на Postgres, иначе LIKE. Без этого поиск был
            // регистрозависимым — «МОДУЛЬ» не находил «Модульная архитектура».
            $like = search_like();
            $mask = '%' . $query . '%';

            $results = News::where('published', true)
                ->where(function ($q) use ($like, $mask) {
                    $q->where('title', $like, $mask)
                      ->orWhere('content', $like, $mask)
                      ->orWhere('meta_description', $like, $mask)
                      ->orWhere('meta_keywords', $like, $mask);
                })
                ->orderByDesc('created_at')
                ->paginate(12)
                ->appends(['q' => $query]);

            // Страницы сайта раньше не искались вовсе — «О проекте», «Возможности»
            // и остальные статические материалы были не найти через поиск.
            $pages = Page::where('published', true)
                ->where(function ($q) use ($like, $mask) {
                    $q->where('title', $like, $mask)
                      ->orWhere('content', $like, $mask)
                      ->orWhere('meta_description', $like, $mask);
                })
                ->orderBy('title')
                ->limit(self::PAGES_LIMIT)
                ->get();
        }

        $total = $results->total() + $pages->count();

        return view('frontend.search.results', compact('results', 'pages', 'query', 'total'));
    }
}
