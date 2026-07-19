<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 📁 Модель категории файлов
 */
class FileCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    /**
     * Родительская категория
     */
    public function parent()
    {
        return $this->belongsTo(FileCategory::class, 'parent_id');
    }

    /**
     * Дочерние категории
     */
    public function children()
    {
        return $this->hasMany(FileCategory::class, 'parent_id');
    }

    /**
     * Файлы в категории
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }
}

