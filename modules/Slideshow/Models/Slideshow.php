<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slideshow extends Model
{
    protected $table = 'slideshows';

    protected $fillable = [
        'title',
        'news_id',
        'position',
        'slug',
        'description',
    ];

    protected static function booted()
    {
        static::deleting(function ($slideshow) {
            $slideshow->items()->delete(); // Удаление связанных слайдов
        });
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(\Modules\News\Models\News::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SlideshowItem::class);
    }
}
