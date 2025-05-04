<?php

namespace Modules\News\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use App\Models\Category;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    // Список всех новостей
    public function index(Request $request)
    {
        $query = News::with('categories');

        // 🔍 Фильтр по шаблону
        if ($request->filled('template')) {
            $query->where('template', $request->input('template'));
        }

        $newsList = $query->orderByDesc('id')->paginate(10);

        // 🔽 Все доступные шаблоны
        $templates = [
            'default' => 'Новости',
            'products' => 'Товары',
            'contacts' => 'Контакты',
            'gallery' => 'Галерея',
        ];

        return view('News::admin.index', compact('newsList', 'templates'));
    }

    // Форма создания новости
    public function create()
    {
        $categories = Category::all();
        return view('News::admin.create', compact('categories'));
    }

    // Сохранение новости
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'categories' => 'nullable|array',
            'published' => 'nullable|boolean',
            'template' => 'nullable|string|max:50',
        ]);

        $news = News::create([
            'title' => $request->title,
            'content' => $request->content,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'published' => $request->boolean('published'),
            'template' => $request->input('template') ?? 'default',
        ]);

        if ($request->filled('categories')) {
            $news->categories()->sync($request->categories);
        }

        return redirect()->route('admin.news.index')->with('success', 'Новость создана!');
    }

    // Форма редактирования
    public function edit(News $news)
    {
        $categories = Category::all();
        return view('News::admin.edit', compact('news', 'categories'));
    }

    // Обновление новости
    public function update(Request $request, News $news)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'categories' => 'nullable|array',
            'published' => 'nullable|boolean',
            'template' => 'nullable|string|max:50',
        ]);

        $news->update([
            'title' => $request->title,
            'content' => $request->content,
            'slug' => Str::slug($request->title),
            'published' => $request->boolean('published'),
            'template' => $request->input('template') ?? 'default',
        ]);

        $news->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.news.index')->with('success', 'Новость обновлена!');
    }

    // Удаление
    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'Новость удалена!');
    }

    public function show($slug)
    {
        // Загрузка новости с категориями и слайдшоу + слайды
        $newsItem = News::with(['categories', 'slideshow.items'])->where('slug', $slug)->firstOrFail();

        return view('News::public.show', compact('newsItem'));
    }
}
