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
    public function index()
    {
        $newsList = News::with('categories')->paginate(10);
        return view('News::admin.index', compact('newsList'));
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
        ]);

        $news = News::create([
            'title' => $request->title,
            'content' => $request->content,
            'slug' => Str::slug($request->title) . '-' . uniqid(), // Уникальный слаг
            'published' => $request->boolean('published'),
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
        ]);

        $news->update([
            'title' => $request->title,
            'content' => $request->content,
            'slug' => Str::slug($request->title), // Можешь оставить .'-'.uniqid(), если нужно уникально при обновлении
            'published' => $request->boolean('published'),
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
}
