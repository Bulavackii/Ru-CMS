<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug'];

    /**
     * Связь многие-ко-многим с новостями.
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\News\Models\News::class, 'news_category');
    }
}
