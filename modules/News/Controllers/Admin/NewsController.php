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
    public function index(Request $request)
    {
        $query = News::with('categories');

        if ($request->filled('template')) {
            $query->where('template', $request->input('template'));
        }

        if ($request->filled('categories')) {
            $categoryIds = array_filter((array) $request->input('categories'));
            if (count($categoryIds)) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        $newsList = $query->orderByDesc('id')->paginate(10);

        $allTemplates = [
            'default'    => 'Новости',
            'products'   => 'Товары',
            'contacts'   => 'Контакты',
            'gallery'    => 'Галерея',
            'slideshow'  => 'Слайдшоу',
            'faq'        => 'Вопросы',
            'reviews'    => 'Отзывы',
            'test'       => 'Тест',
            'base-php'   => 'Уроки PHP база',
            'base-html'  => 'Уроки HTML база',
            'base-css'   => 'Уроки CSS база',
            'base-js'    => 'Уроки JS база',
        ];

        $usedTemplates = News::select('template')->distinct()->pluck('template')->toArray();

        $templates = array_filter(
            $allTemplates,
            fn($key) => in_array($key, $usedTemplates),
            ARRAY_FILTER_USE_KEY
        );

        $categories = Category::all();

        return view('News::admin.index', compact('newsList', 'templates', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        $news = null;
        $templates = $this->loadTemplates();

        return view('News::admin.create', compact('categories', 'templates', 'news'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'nullable|string',
            'categories'       => 'nullable|array',
            'published'        => 'nullable|boolean',
            'template'         => 'nullable|string|max:50',
            'price'            => 'nullable|numeric|min:0',
            'stock'            => 'nullable|integer|min:0',
            'is_promo'         => 'nullable|boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
            'meta_header'      => 'nullable|string|max:255',
        ]);

        $data = [
            'title'            => $request->input('title'),
            'content'          => $request->input('content'),
            'slug'             => Str::slug($request->title) . '-' . uniqid(),
            'published'        => $request->boolean('published'),
            'template'         => $request->input('template', 'default') ?: 'default',
            'meta_title'       => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords'    => $request->input('meta_keywords'),
            'meta_header'      => $request->input('meta_header'),
        ];

        if ($data['template'] === 'products') {
            $data['price'] = $request->input('price');
            $data['stock'] = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        }

        $news = News::create($data);

        if ($request->filled('categories')) {
            $news->categories()->sync($request->categories);
        }

        return redirect()->route('admin.news.index')->with('success', 'Новость создана!');
    }

    public function edit(News $news)
    {
        $categories = Category::all();
        $templates = $this->loadTemplates();

        return view('News::admin.edit', compact('news', 'categories', 'templates'));
    }

    public function update(Request $request, News $news)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'nullable|string',
            'categories'       => 'nullable|array',
            'published'        => 'nullable|boolean',
            'template'         => 'nullable|string|max:50',
            'price'            => 'nullable|numeric|min:0',
            'stock'            => 'nullable|integer|min:0',
            'is_promo'         => 'nullable|boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
            'meta_header'      => 'nullable|string|max:255',
        ]);

        $data = [
            'title'            => $request->input('title'),
            'content'          => $request->input('content'),
            'slug'             => Str::slug($request->title),
            'published'        => $request->boolean('published'),
            'template'         => $request->input('template', 'default') ?: 'default',
            'meta_title'       => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords'    => $request->input('meta_keywords'),
            'meta_header'      => $request->input('meta_header'),
        ];

        if ($data['template'] === 'products') {
            $data['price'] = $request->input('price');
            $data['stock'] = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        }

        $news->update($data);
        $news->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.news.index')->with('success', 'Новость обновлена!');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'Новость удалена!');
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected', []);

        if ($request->action === 'delete') {
            News::whereIn('id', $ids)->delete();
            return back()->with('success', 'Выбранные новости удалены.');
        }

        if ($request->action === 'edit') {
            return redirect()->route('admin.news.bulk.edit', ['ids' => implode(',', $ids)]);
        }

        return back()->with('error', 'Выберите действие.');
    }

    public function bulkEdit(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));
        $news = News::whereIn('id', $ids)->get();
        return view('News::admin.bulk-edit', compact('news'));
    }

    public function bulkUpdate(Request $request)
    {
        $fields = $request->input('fields', []);

        foreach ($fields as $id => $values) {
            $news = News::find($id);
            if ($news) {
                $news->update(array_filter($values));
            }
        }

        return redirect()->route('admin.news.index')->with('success', 'Изменения сохранены.');
    }

    public function show($slug)
    {
        $newsItem = News::with(['categories', 'slideshow.items'])->where('slug', $slug)->firstOrFail();
        return view('News::public.show', compact('newsItem'));
    }

    private function loadTemplates(): array
    {
        $customLabels = [
            'about'     => 'О CMS',
            'default'   => 'Новости',
            'base-php'  => 'Уроки PHP база',
            'base-html' => 'Уроки HTML',
            'base-css'  => 'Уроки CSS',
            'base-js'   => 'Уроки JS база',
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'faq'       => 'Вопросы',
            'reviews'   => 'Отзывы',
            'slideshow' => 'Слайдшоу',
            'gallery'   => 'Галерея',
            'test'      => 'Тест',
        ];

        $templates = [];

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

        // ✅ Добавим все customLabels, даже если файл не был найден по какой-то причине
        foreach ($customLabels as $key => $label) {
            if (!isset($templates[$key])) {
                $file = $templatePath . DIRECTORY_SEPARATOR . $key . '.blade.php';
                if (File::exists($file)) {
                    $templates[$key] = $label;
                }
            }
        }

        ksort($templates); // сортировка по ключу (опционально)

        return $templates;
    }
}
