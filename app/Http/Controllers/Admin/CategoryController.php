<?php

namespace App\Http\Controllers\Admin;

use Modules\Categories\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * 📂 Контроллер управления категориями в админке
 *
 * Позволяет:
 * 🔹 Создавать новые категории (например, для файлов)
 * 🔹 Просматривать список всех категорий
 */
class CategoryController extends Controller
{
    /**
     * ➕ Метод store()
     *
     * 📥 Обработка формы создания новой категории.
     *
     * 🔐 Валидация данных:
     *   - title: обязательно, строка, не длиннее 255 символов
     *   - icon: опционально, строка, максимум 10 символов
     *   - type: задаётся вручную ('file'), не из формы (⚠️ защита от подмены)
     *
     * 🔁 Перенаправление:
     *   - если `redirect_back_to_files` есть в запросе — назад в /admin/files
     *   - иначе — на список всех категорий
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ✅ Валидация входных данных
        $request->validate([
            'title' => 'required|string|max:255',
            'icon'  => 'nullable|string|max:10',
            // 🛡️ Жёстко задаём тип — не доверяем форме
        ]);

        // 💾 Сохраняем новую категорию в базе
        Category::create([
            'title' => $request->input('title'),
            'icon'  => $request->input('icon'),
            'type'  => 'file', // 🔐 Насильно устанавливаем тип
        ]);

        // 🔁 Возврат: если форма пришла со страницы файлов — вернуться туда
        if ($request->has('redirect_back_to_files')) {
            return redirect()
                ->route('admin.files.index')
                ->with('success', '✅ Категория создана!');
        }

        // 🔁 Иначе вернёмся на общий список категорий
        return redirect()
            ->route('admin.categories.index')
            ->with('success', '✅ Категория создана!');
    }

    /**
     * 📄 Метод index()
     *
     * 🔍 Отображение всех категорий в админке
     *
     * 📊 Пагинация: 20 записей на страницу
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 📥 Загружаем все категории, сортировка по убыванию ID
        $categories = Category::orderByDesc('id')->paginate(20);

        // 🖼️ Отображаем представление с таблицей категорий
        return view('admin.categories.index', compact('categories'));
    }
}
