<?php

namespace Modules\News\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\News\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::with('categories')->latest()->paginate(6);
        return view('News::frontend.index', compact('news'));
    }

    public function show($slug)
    {
        $newsItem = News::where('slug', $slug)->with('categories')->firstOrFail();
        return view('News::frontend.show', compact('newsItem'));
    }
}
