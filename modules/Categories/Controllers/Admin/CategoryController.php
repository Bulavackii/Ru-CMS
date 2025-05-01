<?php

namespace Modules\Categories\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Categories\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('created_at', 'desc')->paginate(10);
        return view('Categories::admin.index', compact('categories'));
    }

    public function create()
    {
        return view('Categories::admin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'type' => 'required|string'
        ]);

        Category::create($request->all());

        return redirect()->route('admin.categories.index')->with('success', 'Категория создана!');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('Categories::admin.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255|unique:categories,title,' . $id,
            'description' => 'nullable|string',
            'type' => 'required|string'
        ]);

        $category->update($request->all());

        return redirect()->route('admin.categories.index')->with('success', 'Категория обновлена!');
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Категория удалена!');
    }
}
