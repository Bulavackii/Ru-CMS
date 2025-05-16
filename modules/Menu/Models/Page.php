<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'published',
        'show_on_homepage',
        'homepage_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Category::class, 'page_category');
    }
}
