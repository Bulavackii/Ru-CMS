<?php

namespace Modules\Files\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Files\Models\FileCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class FileCategoryController extends Controller
{
    /**
     * 📋 Список категорий
     */
    public function index(): View
    {
        $categories = FileCategory::with(['parent', 'children', 'files'])
            ->orderBy('name')
            ->get();

        return view('Files::admin.categories', compact('categories'));
    }

    /**
     * 💾 Создание категории
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:file_categories,id',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['name']);

            // Проверка уникальности slug
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (FileCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category = FileCategory::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Категория успешно создана',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Category creation error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания категории: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✏️ Обновление категории
     */
    public function update(Request $request, FileCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:file_categories,id|not_in:' . $category->id,
        ]);

        try {
            // Проверка на циклические зависимости
            if ($validated['parent_id']) {
                $parent = FileCategory::find($validated['parent_id']);
                if ($this->isDescendant($parent, $category)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нельзя установить дочернюю категорию в качестве родительской',
                    ], 400);
                }
            }

            $validated['slug'] = Str::slug($validated['name']);

            // Проверка уникальности slug (кроме текущей категории)
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (FileCategory::where('slug', $validated['slug'])
                ->where('id', '!=', $category->id)
                ->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Категория успешно обновлена',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Category update error', ['error' => $e->getMessage(), 'category_id' => $category->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления категории: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Удаление категории
     */
    public function destroy(FileCategory $category): JsonResponse
    {
        try {
            // Проверка наличия файлов в категории
            if ($category->files()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невозможно удалить категорию, в которой есть файлы',
                ], 400);
            }

            // Проверка наличия дочерних категорий
            if ($category->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невозможно удалить категорию с дочерними категориями',
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Категория успешно удалена',
            ]);
        } catch (\Exception $e) {
            \Log::error('Category deletion error', ['error' => $e->getMessage(), 'category_id' => $category->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления категории: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверка, является ли категория потомком
     */
    private function isDescendant(FileCategory $parent, FileCategory $category): bool
    {
        if ($parent->id === $category->id) {
            return true;
        }

        foreach ($parent->children as $child) {
            if ($this->isDescendant($child, $category)) {
                return true;
            }
        }

        return false;
    }
}




