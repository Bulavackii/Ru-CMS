<?php

namespace Modules\Categories\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Categories\Models\Category;

class CategoryController extends Controller
{
    /**
     * Отображение списка категорий с поддержкой поиска и пагинации.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . trim($request->search) . '%');
        }

        $categories = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('Categories::admin.index', compact('categories'));
    }

    /**
     * Форма создания новой категории.
     */
    public function create()
    {
        return view('Categories::admin.create');
    }

    /**
     * Сохранение новой категории.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:categories,title',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Категория добавлена.');
    }

    /**
     * Форма редактирования существующей категории.
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return view('Categories::admin.edit', compact('category'));
    }

    /**
     * Обновление существующей категории.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:categories,title,' . $category->id,
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Категория обновлена.');
    }

    /**
     * Удаление категории.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Категория удалена.');
    }
}
