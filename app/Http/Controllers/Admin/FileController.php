<?php

namespace App\Http\Controllers\Admin;

use App\Models\File;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 📁 Контроллер управления файлами в админке
 *
 * 🔹 Загрузка, отображение, фильтрация и удаление файлов
 * 🔹 Поддержка категорий (type = 'file')
 */
class FileController extends Controller
{
    /**
     * ⬆️ Метод upload()
     *
     * 📥 Обработка загрузки нового файла
     *
     * 🔐 Валидация:
     * - файл обязателен и должен быть одного из указанных типов
     * - category_id должен существовать в таблице categories
     *
     * 🗂️ Сохраняем файл в `storage/app/public/files`
     * 📌 Создаём запись в БД
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'category_id' => 'required|exists:categories,id',
        ]);

        $file = $request->file('file');

        // 🧩 Уникальное имя файла
        $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();

        // 💾 Сохраняем файл в папку `files` (диск public)
        $path = $file->storeAs('files', $filename, 'public');

        // 🧾 Создаём запись в таблице files
        File::create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category_id' => $request->category_id,
        ]);

        return back()->with('success', '✅ Файл загружен успешно!');
    }

    /**
     * 📋 Метод index()
     *
     * 📑 Список всех загруженных файлов с фильтрацией по категориям
     *
     * 📦 Загружаем только категории с типом 'file'
     * 🔍 Поддержка фильтрации по `category_id` через query string
     * 📄 Пагинация: по 10 файлов на страницу
     */
    public function index(Request $request)
    {
        $currentCategory = $request->input('category');

        // 📂 Все категории типа 'file'
        $categories = Category::where('type', 'file')->get();

        // 🔍 Фильтрация по выбранной категории
        $files = File::when($currentCategory, function ($query) use ($currentCategory) {
            return $query->where('category_id', $currentCategory);
        })->paginate(10)->withQueryString(); // Сохраняем фильтр при переключении страниц

        return view('admin.files.index', compact('files', 'categories', 'currentCategory'));
    }

    /**
     * ⬇️ Метод download()
     *
     * 🗃️ Скачивание файла по ID
     *
     * 🔐 Ищем файл в базе и возвращаем его через Storage
     */
    public function download($id)
    {
        $file = File::findOrFail($id);

        // 📤 Скачиваем файл с оригинальным именем
        return Storage::disk('public')->download($file->path, $file->name);
    }

    /**
     * 🧩 Метод filter()
     *
     * (опционально используется отдельно)
     *
     * 📑 Показывает файлы, отфильтрованные по категории
     */
    public function filter(Request $request)
    {
        $categoryId = $request->get('category');

        // 📂 Все категории
        $categories = Category::where('type', 'file')->get();

        // 📁 Фильтрованные файлы
        $files = File::where('category_id', $categoryId)->get();

        return view('files.index', compact('files', 'categories'));
    }

    /**
     * 🗑️ Метод bulkDelete()
     *
     * 🚫 Массовое удаление выбранных файлов
     *
     * 🔹 Удаляет файл с диска
     * 🔹 Удаляет запись из базы
     */
    public function bulkDelete(Request $request)
    {
        // 📌 Получаем массив ID из строки через запятую
        $ids = explode(',', $request->input('file_ids'));

        if (!empty($ids)) {
            $files = File::whereIn('id', $ids)->get();

            foreach ($files as $file) {
                // 🧼 Удаляем файл с диска
                Storage::disk('public')->delete($file->path);

                // 🧹 Удаляем из базы
                $file->delete();
            }

            return back()->with('success', '🗑️ Выбранные файлы удалены.');
        }

        return back()->with('error', '⚠️ Не выбрано ни одного файла для удаления.');
    }
}
