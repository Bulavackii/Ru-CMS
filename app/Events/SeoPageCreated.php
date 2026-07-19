<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Seo\Models\SeoPage;

class SeoPageCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SeoPage $seoPage)
    {
    }
}




