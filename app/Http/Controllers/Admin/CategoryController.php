<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'icon'  => 'nullable|string|max:10',
            // Жёстко задаём тип — не доверяем форме
        ]);

        Category::create([
            'title' => $request->input('title'),
            'icon'  => $request->input('icon'),
            'type'  => 'file', // гарантированно устанавливаем тип
        ]);

        // Вернём обратно в файлы, если была форма из /admin/files
        if ($request->has('redirect_back_to_files')) {
            return redirect()->route('admin.files.index')->with('success', 'Категория создана!');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Категория создана!');
    }

    public function index()
    {
        // Отображаем все категории, а не только 'file'
        $categories = Category::orderByDesc('id')->paginate(20);
        return view('admin.categories.index', compact('categories'));
    }
}
