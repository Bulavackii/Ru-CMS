<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 📁 File
 *
 * Модель загруженного файла (изображение, видео, документ и т.д.).
 *
 * Связана с:
 * 🔹 Категорией (belongsTo)
 */
class File extends Model
{
    /**
     * 🔓 Поля, доступные для массового заполнения
     *
     * - name        — оригинальное имя файла (например: "document.pdf")
     * - path        — путь до файла в хранилище (например: "files/abc123.pdf")
     * - mime_type   — MIME-тип файла (например: "image/png")
     * - size        — размер файла в байтах
     * - category_id — ID категории, к которой привязан файл
     */
    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'category_id',
    ];

    /**
     * 🔗 category()
     *
     * Связь "многие к одному" с категорией.
     * Каждый файл принадлежит одной категории.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
