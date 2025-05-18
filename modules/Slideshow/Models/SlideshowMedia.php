<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideshowMedia extends Model
{
    // 🗂️ Явно указываем таблицу
    protected $table = 'slideshow_media';

    // ✅ Разрешённые к массовому заполнению поля
    protected $fillable = [
        'slideshow_id',  // 🔗 ID связанного слайдшоу
        'file_path',     // 🖼️ Путь к медиафайлу
        'media_type',    // 🎞️ Тип медиа (image / video)
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
