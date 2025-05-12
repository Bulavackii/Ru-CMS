<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    // Метод для создания новой категории
    public function store(Request $request)
    {
        // Валидация поля категории
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:10', // Иконка категории
        ]);

        // Создание новой категории
        FileCategory::create([
            'name' => $request->name,
            'icon' => $request->icon, // Иконка категории (по желанию)
        ]);

        // Перенаправление на страницу с успешным сообщением
        return redirect()->route('admin.files.index')->with('success', 'Категория успешно создана!');
    }
}
