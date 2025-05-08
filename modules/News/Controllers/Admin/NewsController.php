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

        $newsList = $query->orderByDesc('id')->paginate(10);

        $allTemplates = [
            'default'   => 'Новости',
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'gallery'   => 'Галерея',
            'slideshow' => 'Слайдшоу',
            'faq'       => 'Вопросы',
            'reviews'   => 'Отзывы',
            'test'      => 'Тест',
        ];

        $usedTemplates = News::select('template')->distinct()->pluck('template')->toArray();

        $templates = array_filter(
            $allTemplates,
            fn($key) => in_array($key, $usedTemplates),
            ARRAY_FILTER_USE_KEY
        );

        return view('News::admin.index', compact('newsList', 'templates'));
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
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'categories' => 'nullable|array',
            'published'  => 'nullable|boolean',
            'template'   => 'nullable|string|max:50',
            'price'      => 'nullable|numeric|min:0',
            'stock'      => 'nullable|integer|min:0',
            'is_promo'   => 'nullable|boolean',
        ]);

        $data = [
            'title'     => $request->title,
            'content'   => $request->content,
            'slug'      => Str::slug($request->title) . '-' . uniqid(),
            'published' => $request->boolean('published'),
            'template'  => $request->input('template', 'default') ?: 'default',
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
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'categories' => 'nullable|array',
            'published'  => 'nullable|boolean',
            'template'   => 'nullable|string|max:50',
            'price'      => 'nullable|numeric|min:0',
            'stock'      => 'nullable|integer|min:0',
            'is_promo'   => 'nullable|boolean',
        ]);

        $data = [
            'title'     => $request->title,
            'content'   => $request->content,
            'slug'      => Str::slug($request->title),
            'published' => $request->boolean('published'),
            'template'  => $request->input('template', 'default') ?: 'default',
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

    public function show($slug)
    {
        $newsItem = News::with(['categories', 'slideshow.items'])->where('slug', $slug)->firstOrFail();
        return view('News::public.show', compact('newsItem'));
    }

    private function loadTemplates(): array
    {
        $templates = ['default' => 'Новости'];

        $customLabels = [
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'faq'   => 'Вопросы',
            'reviews' => 'Отзывы',
            'default'      => 'Новости',
            'slideshow'      => 'Слайдшоу',
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
