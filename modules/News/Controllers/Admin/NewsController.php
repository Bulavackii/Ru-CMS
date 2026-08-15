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

        $allTemplates = self::TEMPLATES;

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

        // Цену носят «Товары» И «Услуги»: у услуги она показывается в
        // карточке с оговоркой «от». Раньше проверка была на один шаблон, и
        // сохранение услуги из панели МОЛЧА ОБНУЛЯЛО цену — владелец открывал
        // материал, ничего не менял, нажимал «Сохранить», и карточка на сайте
        // переключалась на «По запросу».
        $data['price'] = in_array($template, self::PRICE_TEMPLATES, true)
            ? $request->input('price')
            : null;

        // Остаток и «распродажа» — только товарные: у услуги склада нет.
        if (in_array($template, self::STOCK_TEMPLATES, true)) {
            $data['stock']    = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        } else {
            $data['stock']    = null;
            $data['is_promo'] = false;
        }

        // Оценка — только у шаблона «Игры». У остальных обнуляем: иначе
        // значение осталось бы висеть после смены шаблона и всплыло бы,
        // когда материал снова станет игровым.
        // Оценку носят два шаблона: «Игры» (обзор) и «Отзывы» (мнение
        // клиента). Раньше только «Игры», поэтому у отзыва поле всегда
        // оставалось NULL и блок оценки не рисовался ВООБЩЕ.
        $data['rating'] = in_array($template, self::RATING_TEMPLATES, true)
            ? ($request->filled('rating') ? (float) $request->input('rating') : null)
            : null;

        $news = News::create($data);

        if ($request->filled('categories')) {
            $news->categories()->sync($request->categories);
        }

        // 🔄 Создать начальную версию
        $this->versioning->createVersion($news, 'Создание новости');

        // 🎯 Используем Event вместо прямого вызова
        NewsCreated::dispatch($news);

        return redirect()->route('admin.news.index')->with('success', __('admin.flash.news_created'));
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

        // Цену носят «Товары» И «Услуги»: у услуги она показывается в
        // карточке с оговоркой «от». Раньше проверка была на один шаблон, и
        // сохранение услуги из панели МОЛЧА ОБНУЛЯЛО цену — владелец открывал
        // материал, ничего не менял, нажимал «Сохранить», и карточка на сайте
        // переключалась на «По запросу».
        $data['price'] = in_array($template, self::PRICE_TEMPLATES, true)
            ? $request->input('price')
            : null;

        // Остаток и «распродажа» — только товарные: у услуги склада нет.
        if (in_array($template, self::STOCK_TEMPLATES, true)) {
            $data['stock']    = $request->input('stock');
            $data['is_promo'] = $request->boolean('is_promo');
        } else {
            $data['stock']    = null;
            $data['is_promo'] = false;
        }

        // Оценка — только у шаблона «Игры». У остальных обнуляем: иначе
        // значение осталось бы висеть после смены шаблона и всплыло бы,
        // когда материал снова станет игровым.
        // Оценку носят два шаблона: «Игры» (обзор) и «Отзывы» (мнение
        // клиента). Раньше только «Игры», поэтому у отзыва поле всегда
        // оставалось NULL и блок оценки не рисовался ВООБЩЕ.
        $data['rating'] = in_array($template, self::RATING_TEMPLATES, true)
            ? ($request->filled('rating') ? (float) $request->input('rating') : null)
            : null;

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
        // Переводы контента на другие языки (блок «Переводы» в форме)
        $news->saveTranslations($request->input('translations', []));

        $news->categories()->sync($request->input('categories', []));

        // 🎯 Используем правильное событие
        NewsUpdated::dispatch($news);

        return redirect()->route('admin.news.index')->with('success', __('admin.flash.news_updated'));
    }

    public function destroy(News $news)
    {
        NewsDeleted::dispatch($news);
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', __('admin.flash.news_deleted'));
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected', []);

        if (empty($ids)) {
            return back()->with('error', __('admin.flash.news_pick'));
        }

        if ($request->action === 'delete') {
            $newsItems = News::whereIn('id', $ids)->get();
            foreach ($newsItems as $news) {
                NewsDeleted::dispatch($news);
            }
            News::whereIn('id', $ids)->delete();
            return back()->with('success', __('admin.flash.news_bulk_deleted'));
        }

        if ($request->action === 'publish') {
            News::whereIn('id', $ids)->update(['published' => true, 'updated_by' => auth()->id()]);
            return back()->with('success', __('admin.flash.news_bulk_published'));
        }

        if ($request->action === 'unpublish') {
            News::whereIn('id', $ids)->update(['published' => false, 'updated_by' => auth()->id()]);
            return back()->with('success', __('admin.flash.news_bulk_unpublished'));
        }

        if ($request->action === 'edit') {
            return redirect()->route('admin.news.bulk.edit', ['ids' => implode(',', $ids)]);
        }

        return back()->with('error', __('admin.flash.pick_action'));
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

        return redirect()->route('admin.news.index')->with('success', __('admin.flash.saved'));
    }

    /**
     * Шаблоны, доступные редактору в форме материала.
     *
     * Раньше список собирался сканом всей папки шаблонов, и в выпадающий
     * список попадало всё подряд: четыре учебных «Урока», «Тест» и
     * демо-страница «RU CMS». Редактор выбирал из шестнадцати пунктов,
     * половина которых ему не нужна.
     *
     * Теперь список явный. Новый шаблон показывается, только когда его
     * сюда добавили, — заодно видно, какие вообще есть.
     */
    public const TEMPLATES = [
        'default'   => 'Новости',
        'magazine'  => 'Журнал',
        'clinic'    => 'Клиника',
        'gaming'    => 'Игры',
        'products'  => 'Товары',
        'ourworks'  => 'Наши услуги',
        'faq'       => 'Вопросы',
        'reviews'   => 'Отзывы',
        'release'   => 'Релизы',
        'slideshow' => 'Слайдшоу',

        // Уроки. Файлы этих шаблонов лежали в проекте, но в списке их не
        // было — выбрать шаблон в форме было НЕЛЬЗЯ, то есть четыре готовых
        // шаблона просто не существовали для владельца.
        'base-html' => 'Уроки: HTML',
        'base-css'  => 'Уроки: CSS',
        'base-js'   => 'Уроки: JavaScript',
        'base-php'  => 'Уроки: PHP',
    ];

    /**
     * Какой шаблон какое поле носит.
     *
     * ⚠️ Эти списки были размазаны по контроллеру (две копии — в store и в
     * update) и по двум вьюхам формы (в JS-переключателе полей). Расхождение
     * такого списка уже случалось: TEMPLATES и правило `in:` в NewsRequest
     * разошлись, и «Игры» с «Клиникой» не сохранялись вовсе. Держим в одном
     * месте, вьюхи берут отсюда же.
     */
    public const PRICE_TEMPLATES  = ['products', 'ourworks'];
    public const STOCK_TEMPLATES  = ['products'];
    public const RATING_TEMPLATES = ['gaming', 'reviews'];

    private function loadTemplates(): array
    {
        // Показываем только те, у которых реально есть файл: иначе
        // выбранный шаблон молча не отрисовался бы на сайте.
        $path = resource_path('views/frontend/templates');

        return array_filter(
            self::TEMPLATES,
            fn ($key) => File::exists($path . DIRECTORY_SEPARATOR . $key . '.blade.php'),
            ARRAY_FILTER_USE_KEY
        );
    }

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
