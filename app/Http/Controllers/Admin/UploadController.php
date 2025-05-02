<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadMedia(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Файл не загружен'], 400);
        }

        $file = $request->file('file');

        // Поддерживаемые MIME-типы
        $allowed = [
            // Изображения
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // Видео
            'video/mp4',
            'video/webm',
            'video/ogg',
            // Документы
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($file->getMimeType(), $allowed)) {
            return response()->json(['error' => 'Тип файла не поддерживается'], 415);
        }

        $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        return response()->json(['location' => asset('storage/' . $path)]);
    }
}
