<?php

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Category::class, 'news_category');
    }
}
