<?php

namespace Modules\Categories\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Categories\Models\Category;
use Modules\Categories\Http\Requests\CategoryRequest;

/**
 * 📦 Админ-контроллер для управления категориями
 */
class CategoryController extends Controller
{
    /**
     * 📄 Список категорий с поддержкой поиска и фильтрации
     */
    public function index(Request $request)
    {
        $query = Category::withCount(['news', 'pages', 'children']);

        // 🔍 Поиск по названию, описанию или slug
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // 🔍 Фильтрация по типу
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // 🔍 Фильтрация по активности
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 🔍 Фильтрация по родителю
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->root();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // 📋 Сортировка
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['title', 'type', 'sort_order', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->ordered();
        }

        // Добавляем сортировку по ID для стабильности
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'asc');
        }

        // 📋 Пагинация
        $categories = $query->paginate($request->get('per_page', 15))
            ->withQueryString();

        // Получаем список родительских категорий для фильтра
        $parentCategories = Category::root()->orderBy('title')->get();
        
        // Получаем уникальные типы категорий для фильтра
        $types = Category::distinct()->pluck('type')->filter()->sort()->values();

        return view('Categories::admin.index', compact('categories', 'parentCategories', 'types'));
    }

    /**
     * ➕ Форма создания новой категории
     */
    public function create()
    {
        $parentCategories = Category::root()->ordered()->get();
        return view('Categories::admin.create', compact('parentCategories'));
    }

    /**
     * 💾 Сохранение новой категории
     */
    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория успешно создана.');
    }

    /**
     * ✏️ Форма редактирования категории
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        
        // Исключаем текущую категорию и её потомков из списка родителей
        $parentCategories = Category::root()
            ->where('id', '!=', $id)
            ->ordered()
            ->get();

        // Получаем счетчики использования
        $usageCounts = $category->getUsageCounts();

        return view('Categories::admin.edit', compact('category', 'parentCategories', 'usageCounts'));
    }

    /**
     * ♻️ Обновление существующей категории
     */
    public function update(CategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);

        // Проверяем, что категория не становится родителем самой себя
        if ($request->parent_id == $category->id) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['parent_id' => 'Категория не может быть родителем самой себя']);
        }

        // Проверяем, что родитель не является потомком текущей категории
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent && $this->isDescendant($parent, $category)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'Категория не может быть родителем своего потомка']);
            }
        }

        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена.');
    }

    /**
     * 🗑️ Удаление одной категории
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Проверяем использование категории
        if ($category->isUsed()) {
            $usageCounts = $category->getUsageCounts();
            $message = 'Невозможно удалить категорию, так как она используется: ';
            $usage = [];
            
            if ($usageCounts['news'] > 0) {
                $usage[] = "новостей ({$usageCounts['news']})";
            }
            if ($usageCounts['pages'] > 0) {
                $usage[] = "страниц ({$usageCounts['pages']})";
            }
            if ($usageCounts['children'] > 0) {
                $usage[] = "дочерних категорий ({$usageCounts['children']})";
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('error', $message . implode(', ', $usage));
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория успешно удалена.');
    }

    /**
     * 🗂️ Массовое удаление категорий
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $ids = $request->input('category_ids');
        $categories = Category::whereIn('id', $ids)->get();

        $cannotDelete = [];
        $canDelete = [];

        foreach ($categories as $category) {
            if ($category->isUsed()) {
                $cannotDelete[] = $category->title;
            } else {
                $canDelete[] = $category->id;
            }
        }

        if (!empty($canDelete)) {
            Category::whereIn('id', $canDelete)->delete();
        }

        $response = ['success' => true, 'deleted' => count($canDelete)];

        if (!empty($cannotDelete)) {
            $response['warning'] = 'Некоторые категории не удалены (используются): ' . implode(', ', $cannotDelete);
        }

        return response()->json($response);
    }

    /**
     * 🔄 Массовое изменение типа
     */
    public function bulkUpdateType(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'type' => 'nullable|string|max:50',
        ]);

        $ids = $request->input('category_ids');
        Category::whereIn('id', $ids)->update(['type' => $request->input('type')]);

        return response()->json(['success' => true, 'updated' => count($ids)]);
    }

    /**
     * 🔄 Массовое изменение родителя
     */
    public function bulkUpdateParent(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        $ids = $request->input('category_ids');
        $parentId = $request->input('parent_id') ?: null;

        // Проверяем, что родитель не входит в список категорий для изменения
        if ($parentId && in_array($parentId, $ids)) {
            return response()->json([
                'error' => 'Родительская категория не может быть среди изменяемых'
            ], 422);
        }

        Category::whereIn('id', $ids)->update(['parent_id' => $parentId]);

        return response()->json(['success' => true, 'updated' => count($ids)]);
    }

    /**
     * 🔄 Массовое изменение активности
     */
    public function bulkUpdateActive(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'is_active' => 'required|boolean',
        ]);

        $ids = $request->input('category_ids');
        Category::whereIn('id', $ids)->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true, 'updated' => count($ids)]);
    }

    /**
     * 🔍 Проверка, является ли категория потомком другой категории
     *
     * @param Category $ancestor
     * @param Category $descendant
     * @return bool
     */
    private function isDescendant(Category $ancestor, Category $descendant): bool
    {
        $current = $descendant->parent;
        while ($current) {
            if ($current->id === $ancestor->id) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }
}
