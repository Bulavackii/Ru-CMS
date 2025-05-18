<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * 📤 UploadController — контроллер для загрузки медиафайлов
 *
 * Используется, например, при загрузке изображений и видео через редактор (TinyMCE и др.)
 */
class UploadController extends Controller
{
    /**
     * 📁 Метод uploadMedia()
     *
     * 🔽 Обрабатывает AJAX-загрузку медиафайлов (изображений, видео, документов)
     * Возвращает JSON-ответ с публичной ссылкой на файл
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMedia(Request $request)
    {
        // ⚠️ Проверка: был ли файл в запросе
        if (!$request->hasFile('file')) {
            return response()->json(['error' => '❌ Файл не загружен'], 400);
        }

        $file = $request->file('file');

        // ✅ Поддерживаемые MIME-типы
        $allowed = [
            // 🖼️ Изображения
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // 🎞️ Видео
            'video/mp4',
            'video/webm',
            'video/ogg',
            // 📄 Документы
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        // 🛑 MIME-тип не входит в список разрешённых
        if (!in_array($file->getMimeType(), $allowed)) {
            return response()->json(['error' => '⚠️ Тип файла не поддерживается'], 415);
        }

        // 🧩 Генерируем уникальное имя файла
        $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();

        // 💾 Сохраняем файл в папку `uploads` на диске `public`
        $path = $file->storeAs('uploads', $filename, 'public');

        // 📦 Возвращаем публичную ссылку на файл (используется в редакторе и т.п.)
        return response()->json([
            'location' => asset('storage/' . $path)
        ]);
    }
}
