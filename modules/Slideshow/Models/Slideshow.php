<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Slideshow extends Model
{
    // 🗃️ Таблица базы данных
    protected $table = 'slideshows';

    // 📝 Массово заполняемые поля
    protected $fillable = [
        'title',            // 📛 Название слайдшоу
        'news_id',          // 📰 ID связанной новости (если есть)
        'position',         // 📍 Позиция размещения (top/bottom)
        'slug',             // 🔗 ЧПУ
        'description',      // 📝 Описание
        'published',        // ✅ Опубликовано
        'autoplay_delay',   // ⏱️ Задержка автоплея (мс)
        'transition_effect', // 🎬 Эффект перехода (slide, fade, etc.)
        'height',           // 📏 Высота слайдера
        'show_pagination',  // 🔘 Показывать пагинацию
        'show_navigation',  // ⬅️➡️ Показывать навигацию
    ];

    // 🔢 Значения по умолчанию для атрибутов
    protected $attributes = [
        'published' => false,
        'autoplay_delay' => 5000,
        'transition_effect' => 'slide',
        'show_pagination' => true,
        'show_navigation' => true,
    ];

    // 🎯 Приведение типов
    protected $casts = [
        'published' => 'boolean',
        'autoplay_delay' => 'integer',
        'show_pagination' => 'boolean',
        'show_navigation' => 'boolean',
    ];

    /**
     * 🧼 Автоматическое удаление слайдов при удалении слайдшоу
     * 🔄 Инвалидация кэша при изменениях
     */
    protected static function booted()
    {
        static::deleting(function ($slideshow) {
            // 🧹 Удаление связанных элементов
            $slideshow->items()->delete();
            // 🗑️ Очистка кэша
            Cache::forget("slideshows_{$slideshow->position}");
            Cache::forget('home_slideshows');
        });

        static::saved(function ($slideshow) {
            // 🔄 Очистка кэша при сохранении
            Cache::forget("slideshows_{$slideshow->position}");
            Cache::forget('home_slideshows');
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
