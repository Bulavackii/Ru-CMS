<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;

class SlideshowItem extends Model
{
    protected $fillable = [
        'slideshow_id',
        'file_path',
        'media_type',
        'caption',       
        'order',
    ];

    public function slideshow()
    {
        return $this->belongsTo(Slideshow::class);
    }
}
