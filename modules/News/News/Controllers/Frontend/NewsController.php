<?php

namespace Modules\News\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\News\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $newsList = News::with('categories')
            ->where('published', true) // ← фильтрация по опубликованным
            ->orderByDesc('id')
            ->paginate(10);

        return view('frontend.news.index', compact('newsList'));
    }

    public function show($slug)
    {
        $news = News::with('categories')
            ->where('slug', $slug)
            ->where('published', true) // ← показываем только опубликованные
            ->firstOrFail();

        return view('frontend.news.show', compact('news'));
    }
}
