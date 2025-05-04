<?php

namespace Modules\News\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\News\Models\News;
use App\Models\Category;

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

        // 🔽 Статический список для фильтра
        $templates = [
            'default'   => 'Новости',
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'gallery'   => 'Галерея',
            'slideshow' => 'Слайдшоу',
            'test2'     => 'Тест2',
            'test'      => 'Тест',
        ];

        return view('News::admin.index', compact('newsList', 'templates'));
    }

    // Форма создания новости
    public function create()
    {
        $categories = Category::all();
        $news = null;
        $templates = $this->loadTemplates();

        return view('News::admin.create', compact('categories', 'templates', 'news'));
    }

    // Сохранение новости
    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'categories' => 'nullable|array',
            'published'  => 'nullable|boolean',
            'template'   => 'nullable|string|max:50',
        ]);

        $news = News::create([
            'title'     => $request->title,
            'content'   => $request->content,
            'slug'      => Str::slug($request->title) . '-' . uniqid(),
            'published' => $request->boolean('published'),
            'template'  => $request->input('template') ?? 'default',
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
        $templates = $this->loadTemplates();

        return view('News::admin.edit', compact('news', 'categories', 'templates'));
    }

    // Обновление новости
    public function update(Request $request, News $news)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'categories' => 'nullable|array',
            'published'  => 'nullable|boolean',
            'template'   => 'nullable|string|max:50',
        ]);

        $news->update([
            'title'     => $request->title,
            'content'   => $request->content,
            'slug'      => Str::slug($request->title),
            'published' => $request->boolean('published'),
            'template'  => $request->input('template') ?? 'default',
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

    // Просмотр новости (для публичной части)
    public function show($slug)
    {
        $newsItem = News::with(['categories', 'slideshow.items'])->where('slug', $slug)->firstOrFail();
        return view('News::public.show', compact('newsItem'));
    }

    // 🔽 Подгрузка шаблонов из папки templates
    private function loadTemplates(): array
    {
        $templates = ['default' => 'Новости'];

        $customLabels = [
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'gallery'   => 'Галерея',
            'slideshow' => 'Слайдшоу',
            'test2'     => 'Тест 2',
            'test'      => 'Тест',
        ];

        $templatePath = resource_path('views/frontend/templates');

        if (File::exists($templatePath)) {
            foreach (File::files($templatePath) as $file) {
                $filename = $file->getFilename();
                if (str_ends_with($filename, '.blade.php')) {
                    $key = basename($filename, '.blade.php');
                    $templates[$key] = $customLabels[$key] ?? ucfirst($key);
                }
            }
        }

        return $templates;
    }
}
