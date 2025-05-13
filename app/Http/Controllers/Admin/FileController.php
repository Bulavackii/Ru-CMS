<?php

namespace App\Http\Controllers\Admin;

use App\Models\File;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    // Загрузка файла
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,gif,mp4,pdf,doc,docx,xls,xlsx,webm,ogg',
            'category_id' => 'required|exists:categories,id',
        ]);

        $file = $request->file('file');
        $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('files', $filename, 'public');

        File::create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category_id' => $request->category_id,
        ]);

        return back()->with('success', 'Файл загружен успешно!');
    }

    // Список файлов
    public function index(Request $request)
    {
        $currentCategory = $request->input('category');
        $categories = Category::where('type', 'file')->get();

        $files = File::when($currentCategory, function ($query) use ($currentCategory) {
            return $query->where('category_id', $currentCategory);
        })->paginate(10)->withQueryString(); // Сохраняем фильтр при пагинации

        return view('admin.files.index', compact('files', 'categories', 'currentCategory'));
    }

    // Скачать файл
    public function download($id)
    {
        $file = File::findOrFail($id);

        return Storage::disk('public')->download($file->path, $file->name);
    }

    // Фильтрация по категориям (если используется отдельно)
    public function filter(Request $request)
    {
        $categoryId = $request->get('category');
        $files = File::where('category_id', $categoryId)->get();
        $categories = Category::where('type', 'file')->get();

        return view('files.index', compact('files', 'categories'));
    }

    public function bulkDelete(Request $request)
    {
        $ids = explode(',', $request->input('file_ids'));

        if (!empty($ids)) {
            \App\Models\File::whereIn('id', $ids)->delete();
            return back()->with('success', 'Выбранные файлы удалены.');
        }

        return back()->with('error', 'Не выбрано ни одного файла для удаления.');
    }
}
