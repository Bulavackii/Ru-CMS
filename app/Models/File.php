<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'path', 'mime_type', 'size', 'category_id'];

    public function category()
    {
        return $this->belongsTo(FileCategory::class, 'category_id');
    }
}
