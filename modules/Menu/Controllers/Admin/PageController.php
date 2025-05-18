<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Page;
use App\Models\Category;
use Illuminate\Support\Str;

/**
 * 📄 Контроллер управления статическими страницами (модуль Menu)
 *
 * 🔹 Поддержка CRUD-операций
 * 🔹 Привязка к категориям
 * 🔹 SEO-поля и slug
 * 🔹 Отображение на главной
 */
class PageController extends Controller
{
    /**
     * 📋 Метод index()
     *
     * 🧾 Список всех страниц (с категориями)
     */
    public function index(Request $request)
    {
        $query = $request->input('q');
        $pages = Page::with('categories')
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->appends(['q' => $query]);

        return view('Menu::admin.pages.index', compact('pages', 'query'));
    }

    /**
     * ➕ Метод create()
     *
     * 🧱 Форма создания новой страницы
     */
    public function create()
    {
        $categories = Category::all();

        // 🔧 Значения по умолчанию
        $page = new Page([
            'published' => true,
            'show_on_homepage' => false,
            'homepage_order' => 0,
        ]);

        return view('Menu::admin.pages.create', compact('categories', 'page'));
    }

    /**
     * 💾 Метод store()
     *
     * 📝 Сохраняет новую страницу в БД
     */
    public function store(Request $request)
    {
        // 📑 Валидация данных формы
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages',
            'content' => 'nullable|string',
            'published' => 'boolean',
            'show_on_homepage' => 'boolean',
            'homepage_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'categories' => 'array',
        ]);

        // 🔗 Генерация slug, если не указан
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']) . '-' . uniqid();

        // ✅ Создание страницы
        $page = Page::create($data);

        // 🔗 Привязка категорий
        $page->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.pages.index')->with('success', 'Страница создана.');
    }

    /**
     * ✏️ Метод edit()
     *
     * 🔧 Форма редактирования страницы
     */
    public function edit(Page $page)
    {
        $categories = Category::all();
        return view('Menu::admin.pages.edit', compact('page', 'categories'));
    }

    /**
     * 🔁 Метод update()
     *
     * ♻️ Обновляет данные страницы
     */
    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
            'content'          => 'nullable|string',
            'homepage_order'   => 'nullable|integer|min:0',
            'categories'       => 'nullable|array',
        ]);

        // 🧩 Чекбоксы: если не переданы — значит false
        $data['published'] = $request->has('published');
        $data['show_on_homepage'] = $request->has('show_on_homepage');

        // 🆕 Сгенерировать slug при отсутствии
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        }

        // 💾 Обновление страницы
        $page->update($data);

        // 🔗 Обновляем привязку к категориям
        $page->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.pages.index')->with('success', 'Страница обновлена.');
    }

    /**
     * 🗑️ Метод destroy()
     *
     * ❌ Удаляет выбранную страницу
     */
    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Страница удалена.');
    }

    public function preview(Page $page)
    {
        return Str::limit(strip_tags($page->content), 500, '...');
    }
}
