<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideshowItem extends Model
{
    // 📝 Массово заполняемые поля
    protected $fillable = [
        'slideshow_id',  // 🔗 ID связанного слайдшоу
        'file_path',     // 🖼️ Путь к файлу (изображение или видео)
        'media_type',    // 🎞️ Тип медиа (image / video)
        'caption',       // 💬 Подпись к слайду
        'link',          // 💬 ССылка от слайда
        'order',         // 🔢 Порядок отображения
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
