<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * 📁 Модель файла
 */
class File extends Model
{
    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'category_id',
        'user_id',
        'alt_text',
        'description',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Категория файла
     */
    public function category()
    {
        return $this->belongsTo(FileCategory::class);
    }

    /**
     * Пользователь, загрузивший файл
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Получить URL файла
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Получить полный путь к файлу
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('public')->path($this->path);
    }

    /**
     * Проверка, является ли файл изображением
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Получить размер файла в человекочитаемом формате
     */
    public function getHumanSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Получить URL thumbnail
     */
    public function getThumbnailUrl(string $size = 'thumb'): ?string
    {
        if (!$this->isImage()) {
            return null;
        }

        $pathInfo = pathinfo($this->path);
        $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }

        // Если thumbnail не существует, возвращаем оригинал
        return $this->url;
    }

    /**
     * Проверка, является ли файл видео
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Проверка, является ли файл документом
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

