<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\News\Models\News;

class NewsUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public News $news)
    {
    }
}




