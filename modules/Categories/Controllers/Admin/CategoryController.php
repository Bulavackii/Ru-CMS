<?php

namespace Modules\Categories\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Categories\Models\Category;

class CategoryController extends Controller
{
    // Список категорий с поиском
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderByDesc('id')->paginate(10);

        return view('Categories::admin.index', compact('categories'));
    }

    // Форма создания
    public function create()
    {
        return view('Categories::admin.create');
    }

    // Сохранение категории
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        Category::create(['title' => $request->title]);

        return redirect()->route('admin.categories.index')->with('success', 'Категория добавлена.');
    }

    // Форма редактирования
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('Categories::admin.edit', compact('category'));
    }

    // Обновление категории
    public function update(Request $request, $id)
    {
        $request->validate(['title' => 'required|string|max:255']);

        $category = Category::findOrFail($id);
        $category->update(['title' => $request->title]);

        return redirect()->route('admin.categories.index')->with('success', 'Категория обновлена.');
    }

    // Удаление
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Категория удалена');
    }

    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        $ids = explode(',', $request->input('category_ids'));

        if (!empty($ids)) {
            \App\Models\Category::whereIn('id', $ids)->delete();
            return redirect()->route('admin.categories.index')->with('success', 'Выбранные категории удалены.');
        }

        return redirect()->route('admin.categories.index')->with('error', 'Вы не выбрали ни одной категории.');
    }
}
