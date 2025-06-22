<?php

namespace Modules\Categories\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Categories\Models\Category;

/**
 * 📦 Админ-контроллер для управления категориями
 */
class CategoryController extends Controller
{
    /**
     * 📄 Список категорий с поддержкой поиска
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // 🔍 Фильтрация по названию категории
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // 📋 Пагинация и сортировка по убыванию ID
        $categories = $query->orderByDesc('id')->paginate(10);

        return view('Categories::admin.index', compact('categories'));
    }

    /**
     * ➕ Форма создания новой категории
     */
    public function create()
    {
        return view('Categories::admin.create');
    }

    /**
     * 💾 Сохранение новой категории
     */
    public function store(Request $request)
    {
        // ✅ Валидация данных
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // 🛠️ Создание записи
        Category::create([
            'title' => $request->title
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория добавлена.');
    }

    /**
     * ✏️ Форма редактирования категории
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('Categories::admin.edit', compact('category'));
    }

    /**
     * ♻️ Обновление существующей категории
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'title' => $request->title
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория обновлена.');
    }

    /**
     * 🗑️ Удаление одной категории
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория удалена');
    }

    /**
     * 🗂️ Массовое удаление категорий
     *
     * Ожидает строку с ID через запятую: category_ids=1,2,3
     */
    public function bulkDelete(Request $request)
    {
        $ids = explode(',', $request->input('category_ids'));

        if (!empty($ids)) {
            Category::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Не выбраны категории.'], 422);
    }
}
