<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideshowItem extends Model
{
    // 📝 Массово заполняемые поля
    protected $fillable = [
        'slideshow_id',     // 🔗 ID связанного слайдшоу
        'file_path',        // 🖼️ Путь к файлу (изображение или видео)
        'media_type',       // 🎞️ Тип медиа (image / video)
        'caption',          // 💬 Подпись к слайду
        'link',             // 🔗 Ссылка от слайда
        'order',            // 🔢 Порядок отображения
        'alt_text',         // 🔍 Alt-текст для SEO
        'text_position',    // 📍 Позиция текста (top-left, top-center, etc.)
        'text_color',       // 🎨 Цвет текста
        'background_color', // 🎨 Цвет фона текста
    ];

    // 🎯 Приведение типов
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * 🔗 Связь с родительским слайдшоу
     *
     * @return BelongsTo
     */
    public function slideshow(): BelongsTo
    {
        return $this->belongsTo(Slideshow::class);
    }
}
