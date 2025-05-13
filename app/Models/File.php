<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['name', 'path', 'mime_type', 'size', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
