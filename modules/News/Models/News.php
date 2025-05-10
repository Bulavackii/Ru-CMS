<?php

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'content',
        'slug',
        'published',
        'template',
        'price',
        'stock',
        'is_promo',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_header',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Category::class, 'news_category');
    }

    public function slideshow()
    {
        return $this->hasOne(\Modules\Slideshow\Models\Slideshow::class, 'news_id');
    }
}
