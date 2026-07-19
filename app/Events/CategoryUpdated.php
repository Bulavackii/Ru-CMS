<?php

namespace App\Events;

use Modules\Categories\Models\Category;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 📢 Событие обновления категории
 */
class CategoryUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Category
     */
    public $category;

    /**
     * Create a new event instance.
     *
     * @param Category $category
     */
    public function __construct(Category $category)
    {
        $this->category = $category;
    }
}




