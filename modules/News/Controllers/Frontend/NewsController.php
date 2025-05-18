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
            ->where('published', true)
            ->firstOrFail();

        return view('frontend.news.show', [
            'news' => $news,
            'meta_title' => $news->meta_title ?? $news->title,
            'meta_description' => $news->meta_description,
            'meta_keywords' => $news->meta_keywords,
            'title' => $news->title, // для <title> в Blade
        ]);
    }
}
