<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Seo\Models\RedirectRule;

class RedirectRuleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RedirectRule $redirectRule)
    {
    }
}




