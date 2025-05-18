<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slideshow extends Model
{
    // 🗃️ Таблица базы данных
    protected $table = 'slideshows';

    // 📝 Массово заполняемые поля
    protected $fillable = [
        'title',        // 📛 Название слайдшоу
        'news_id',      // 📰 ID связанной новости (если есть)
        'position',     // 📍 Позиция размещения (top/bottom)
        'slug',         // 🔗 ЧПУ
        'description',  // 📝 Описание
    ];

    /**
     * 🧼 Автоматическое удаление слайдов при удалении слайдшоу
     */
    protected static function booted()
    {
        static::deleting(function ($slideshow) {
            // 🧹 Удаление связанных элементов
            $slideshow->items()->delete();
        });
    }

    /**
     * 📰 Связь с моделью News
     *
     * @return BelongsTo
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(\Modules\News\Models\News::class);
    }

    /**
     * 🖼️ Связь с элементами слайдшоу
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SlideshowItem::class);
    }
}
