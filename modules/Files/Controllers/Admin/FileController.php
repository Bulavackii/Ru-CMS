<?php

namespace Modules\Files\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Files\Models\File;
use Modules\Files\Models\FileCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileController extends Controller
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * 📁 Список файлов
     */
    public function index(Request $request): View
    {
        $query = File::with(['category', 'user']);

        // Фильтры
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            if ($request->type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($request->type === 'video') {
                $query->where('mime_type', 'like', 'video/%');
            } elseif ($request->type === 'document') {
                $query->whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
            }
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('original_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $files = $query->orderByDesc('created_at')->paginate(24);
        $categories = FileCategory::orderBy('name')->get();

        return view('Files::admin.index', compact('files', 'categories'));
    }

    /**
     * 📤 Загрузка файла(ов)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'sometimes|file|max:10240', // 10MB для одного файла
            'files' => 'sometimes|array',
            'files.*' => 'file|max:10240',
            'category_id' => 'nullable|exists:file_categories,id',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $uploadedFiles = [];
            
            // Поддержка как одного файла, так и массива файлов
            if ($request->hasFile('file')) {
                $uploadedFiles[] = $request->file('file');
            } elseif ($request->hasFile('files')) {
                $uploadedFiles = $request->file('files');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не был загружен',
                ], 400);
            }

            $createdFiles = [];

            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile->isValid()) {
                    continue;
                }

                $originalName = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                // Сохранение файла
                $path = $uploadedFile->store('files/' . date('Y/m'), 'public');
                
                $width = null;
                $height = null;

                // Обработка изображений
                if (str_starts_with($mimeType, 'image/')) {
                    try {
                        $image = $this->imageManager->read(Storage::disk('public')->path($path));
                        $width = $image->width();
                        $height = $image->height();

                        // Создание thumbnails
                        $this->createThumbnails($path);
                    } catch (\Exception $e) {
                        // Если не удалось обработать изображение, продолжаем без размеров
                        \Log::warning('Failed to process image', ['error' => $e->getMessage()]);
                    }
                }

                $file = File::create([
                    'name' => basename($path),
                    'original_name' => $originalName,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'width' => $width,
                    'height' => $height,
                    'category_id' => $request->category_id,
                    'user_id' => auth()->id(),
                    'alt_text' => $request->alt_text,
                    'description' => $request->description,
                ]);

                $createdFiles[] = [
                    'id' => $file->id,
                    'url' => $file->url,
                    'name' => $file->original_name,
                    'size' => $file->human_size,
                ];
            }

            return response()->json([
                'success' => true,
                'files' => $createdFiles,
                'message' => count($createdFiles) . ' файл(ов) успешно загружено',
            ]);
        } catch (\Exception $e) {
            \Log::error('File upload error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🖼️ Обрезка изображения
     */
    public function crop(Request $request, File $file): JsonResponse
    {
        if (!$file->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не является изображением',
            ], 400);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден на диске',
            ], 404);
        }

        $validated = $request->validate([
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        try {
            $image = $this->imageManager->read(Storage::disk('public')->path($file->path));
            
            $image->crop(
                (int)$validated['width'],
                (int)$validated['height'],
                (int)$validated['x'],
                (int)$validated['y']
            );

            Storage::disk('public')->put($file->path, $image->encode());

            // Обновить размеры
            $file->update([
                'width' => $image->width(),
                'height' => $image->height(),
            ]);

            // Пересоздать thumbnails
            $this->createThumbnails($file->path);

            return response()->json([
                'success' => true,
                'message' => 'Изображение обрезано',
                'url' => $file->url,
                'width' => $file->width,
                'height' => $file->height,
            ]);
        } catch (\Exception $e) {
            \Log::error('Image crop error', ['error' => $e->getMessage(), 'file_id' => $file->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обрезки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Удаление файла
     */
    public function destroy(File $file): JsonResponse
    {
        try {
            // Проверка существования файла
            if (Storage::disk('public')->exists($file->path)) {
                // Удалить файл с диска
                Storage::disk('public')->delete($file->path);
                
                // Удалить thumbnails
                $this->deleteThumbnails($file->path);
            }

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Файл удален',
            ]);
        } catch (\Exception $e) {
            \Log::error('File deletion error', ['error' => $e->getMessage(), 'file_id' => $file->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 👁️ Просмотр информации о файле
     */
    public function show(File $file): JsonResponse
    {
        $file->load(['category', 'user']);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'name' => $file->original_name,
                'url' => $file->url,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'human_size' => $file->human_size,
                'width' => $file->width,
                'height' => $file->height,
                'is_image' => $file->isImage(),
                'alt_text' => $file->alt_text,
                'description' => $file->description,
                'category' => $file->category ? [
                    'id' => $file->category->id,
                    'name' => $file->category->name,
                ] : null,
                'user' => $file->user ? [
                    'id' => $file->user->id,
                    'name' => $file->user->name,
                ] : null,
                'created_at' => $file->created_at->format('d.m.Y H:i'),
                'updated_at' => $file->updated_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * ✏️ Обновление метаданных файла
     */
    public function update(Request $request, File $file): JsonResponse
    {
        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:file_categories,id',
            'tags' => 'nullable|array',
        ]);

        try {
            $file->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно обновлен',
                'file' => [
                    'id' => $file->id,
                    'alt_text' => $file->alt_text,
                    'description' => $file->description,
                    'category_id' => $file->category_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📥 Скачивание файла
     */
    public function download(File $file)
    {
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    /**
     * 🖼️ Создание thumbnails
     */
    private function createThumbnails(string $path): void
    {
        $sizes = [
            'thumb' => [150, 150],
            'small' => [300, 300],
            'medium' => [800, 800],
        ];

        $image = $this->imageManager->read(Storage::disk('public')->path($path));

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnail = clone $image;
            $thumbnail->scale($dimensions[0], $dimensions[1]);
            
            $thumbnailPath = $this->getThumbnailPath($path, $sizeName);
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode());
        }
    }

    /**
     * 🗑️ Удаление thumbnails
     */
    private function deleteThumbnails(string $path): void
    {
        $sizes = ['thumb', 'small', 'medium'];
        
        foreach ($sizes as $size) {
            $thumbnailPath = $this->getThumbnailPath($path, $size);
            Storage::disk('public')->delete($thumbnailPath);
        }
    }

    /**
     * 📍 Получить путь к thumbnail
     */
    private function getThumbnailPath(string $path, string $size): string
    {
        $pathInfo = pathinfo($path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
    }
}

