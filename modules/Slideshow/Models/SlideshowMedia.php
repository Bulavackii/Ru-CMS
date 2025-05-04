<?php

namespace Modules\Slideshow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideshowMedia extends Model
{
    protected $table = 'slideshow_media';

    protected $fillable = [
        'slideshow_id',
        'file_path',
        'media_type',
    ];

    public function slideshow(): BelongsTo
    {
        return $this->belongsTo(Slideshow::class);
    }
}
