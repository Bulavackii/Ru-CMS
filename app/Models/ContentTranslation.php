<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 🌐 Перевод одного поля одной записи на один язык.
 *
 * Хранилище общее для всех модулей: меню, страницы, новости, категории,
 * фрагменты, слайды. Подключается трейтом App\Support\HasContentTranslations.
 */
class ContentTranslation extends Model
{
    protected $table = 'content_translations';

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
