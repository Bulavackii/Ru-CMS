<?php

namespace Modules\News\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsRequest;
use App\Events\NewsCreated;
use App\Events\NewsUpdated;
use App\Events\NewsDeleted;
use App\Services\VersioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\News\Models\News;
use Modules\Categories\Models\Category;

class NewsController extends Controller
{
    protected VersioningService $versioning;

    public function __construct(VersioningService $versioning)
    {
        $this->versioning = $versioning;
    }

    public function index(Request $request)
    {
        $query = News::with('categories');

        // Поиск
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Фильтр по шаблону
        if ($request->filled('template')) {
            $query->byTemplate($request->input('template'));
        }

        // Фильтр по статусу публикации
        if ($request->filled('published')) {
            if ($request->input('published') === '1') {
                $query->published();
            } elseif ($request->input('published') === '0') {
                $query->where('published', false);
            }
        }

        // Фильтр по категориям
        if ($request->filled('categories')) {
            $categoryIds = array_filter((array) $request->input('categories'));
            if (count($categoryIds)) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        // Сортировка
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['id', 'title', 'created_at', 'updated_at', 'published'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('id');
        }

        $newsList = $query->paginate(10);

        $allTemplates = [
            'default'   => 'Новости',
            'ourworks'  => 'Наши услуги',
            'release'   => 'Релизы',
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'gallery'   => 'Галерея',
            'slideshow' => 'Слайдшоу',
            'faq'       => 'Вопросы',
            'reviews'   => 'Отзывы',
            'test'      => 'Тест',
            'base-php'  => 'Уроки PHP база',
            'base-html' => 'Уроки HTML база',
            'base-css'  => 'Уроки CSS база',
            'base-js'   => 'Уроки JS база',
        ];

        $usedTemplates = News::select('template')->distinct()->pluck('template')->toArray();

        $templates = array_filter(
            $allTemplates,
            fn ($key) => in_array($key, $usedTemplates),
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

    public function store(NewsRequest $request)
    {
        $template = $request->input('template', 'default') ?: 'default';

        // Генерация slug
        $slug = $request->input('slug');
        if (empty($slug)) {
            $slug = Str::slug($request->title);
            $baseSlug = $slug;
            $counter = 1;
            while (News::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        $data = [
            'title'            => $request->input('title'),
            'content'          => $request->input('content'),
            'slug'             => $slug,
            'published'        => $request->boolean('published'),
            'template'         => $template,
            'meta_title'       => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords'    => $request->input('meta_keywords'),
            'meta_header'      => $request->input('meta_header'),
            'created_by'       => auth()->id(),
        ];

        if ($template === 'products') {
            $data['price']    = $request->input('price');
            $data['stock']    = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        } else {
            $data['price']    = null;
            $data['stock']    = null;
            $data['is_promo'] = false;
        }

        $news = News::create($data);

        if ($request->filled('categories')) {
            $news->categories()->sync($request->categories);
        }

        // 🔄 Создать начальную версию
        $this->versioning->createVersion($news, 'Создание новости');

        // 🎯 Используем Event вместо прямого вызова
        NewsCreated::dispatch($news);

        return redirect()->route('admin.news.index')->with('success', 'Новость создана!');
    }

    public function edit(News $news)
    {
        $categories = Category::all();
        $templates  = $this->loadTemplates();

        return view('News::admin.edit', compact('news', 'categories', 'templates'));
    }

    public function update(NewsRequest $request, News $news)
    {
        $template = $request->input('template', 'default') ?: 'default';

        $data = [
            'title'            => $request->input('title'),
            'content'          => $request->input('content'),
            'published'        => $request->boolean('published'),
            'template'         => $template,
            'meta_title'       => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords'    => $request->input('meta_keywords'),
            'meta_header'      => $request->input('meta_header'),
        ];

        if ($template === 'products') {
            $data['price']    = $request->input('price');
            $data['stock']    = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        } else {
            $data['price']    = null;
            $data['stock']    = null;
            $data['is_promo'] = false;
        }

        // 🔄 Создать версию перед обновлением
        $changes = $this->detectChanges($news, $data);
        $this->versioning->createVersion($news, $changes);

        // Обновление slug, если он изменен
        if ($request->filled('slug') && $request->input('slug') !== $news->slug) {
            $newSlug = $request->input('slug');
            $baseSlug = $newSlug;
            $counter = 1;
            while (News::where('slug', $newSlug)->where('id', '!=', $news->id)->exists()) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $newSlug;
        }

        $data['updated_by'] = auth()->id();
        $news->update($data);
        $news->categories()->sync($request->input('categories', []));

        // 🎯 Используем правильное событие
        NewsUpdated::dispatch($news);

        return redirect()->route('admin.news.index')->with('success', 'Новость обновлена!');
    }

    public function destroy(News $news)
    {
        NewsDeleted::dispatch($news);
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'Новость удалена!');
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected', []);

        if (empty($ids)) {
            return back()->with('error', 'Выберите новости для действия.');
        }

        if ($request->action === 'delete') {
            $newsItems = News::whereIn('id', $ids)->get();
            foreach ($newsItems as $news) {
                NewsDeleted::dispatch($news);
            }
            News::whereIn('id', $ids)->delete();
            return back()->with('success', 'Выбранные новости удалены.');
        }

        if ($request->action === 'publish') {
            News::whereIn('id', $ids)->update(['published' => true, 'updated_by' => auth()->id()]);
            return back()->with('success', 'Выбранные новости опубликованы.');
        }

        if ($request->action === 'unpublish') {
            News::whereIn('id', $ids)->update(['published' => false, 'updated_by' => auth()->id()]);
            return back()->with('success', 'Выбранные новости сняты с публикации.');
        }

        if ($request->action === 'edit') {
            return redirect()->route('admin.news.bulk.edit', ['ids' => implode(',', $ids)]);
        }

        return back()->with('error', 'Выберите действие.');
    }

    public function bulkEdit(Request $request)
    {
        $ids  = explode(',', $request->input('ids', ''));
        $news = News::whereIn('id', $ids)->get();
        return view('News::admin.bulk-edit', compact('news'));
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*' => 'array',
        ]);

        $fields = $request->input('fields', []);

        foreach ($fields as $id => $values) {
            $news = News::find($id);
            if ($news) {
                $updateData = array_filter($values, fn($v) => $v !== null && $v !== '');
                $updateData['updated_by'] = auth()->id();
                $news->update($updateData);
                NewsUpdated::dispatch($news);
            }
        }

        return redirect()->route('admin.news.index')->with('success', 'Изменения сохранены.');
    }

    private function loadTemplates(): array
    {
        return Cache::remember('news_templates', 3600, function () {
            $customLabels = [
                'about'     => 'RU CMS',
                'default'   => 'Новости',
                'ourworks'  => 'Наши услуги',
                'release'   => 'Релизы',
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

            foreach ($customLabels as $key => $label) {
                if (!isset($templates[$key])) {
                    $file = $templatePath . DIRECTORY_SEPARATOR . $key . '.blade.php';
                    if (File::exists($file)) {
                        $templates[$key] = $label;
                    }
                }
            }

            ksort($templates);

            return $templates;
        });
    }

    /**
     * Определить изменения для версионирования
     */
    private function detectChanges(News $news, array $newData): string
    {
        $changes = [];
        
        foreach ($newData as $key => $value) {
            if (isset($news->$key) && $news->$key != $value) {
                $changes[] = $key;
            }
        }

        return !empty($changes) ? 'Изменены: ' . implode(', ', $changes) : 'Обновление';
    }
}
