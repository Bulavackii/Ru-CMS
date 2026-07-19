<?php

namespace App\Observers;

use Modules\Categories\Models\Category;
use Modules\Menu\Models\MenuItem;
use App\Events\CategoryCreated;
use App\Events\CategoryUpdated;
use App\Events\CategoryDeleted;

/**
 * Observer для модели Category
 * Автоматически обновляет URL в пунктах меню при изменении slug категории
 * Диспатчит события при создании, обновлении и удалении
 */
class CategoryObserver
{
    /**
     * Обработка события после создания категории
     */
    public function created(Category $category): void
    {
        CategoryCreated::dispatch($category);
    }

    /**
     * Обработка события после обновления категории
     */
    public function updated(Category $category): void
    {
        // Если изменился slug, обновляем URL в пунктах меню
        if ($category->wasChanged('slug') && $category->getOriginal('slug')) {
            $oldSlug = $category->getOriginal('slug');
            $newSlug = $category->slug;

            // Находим все пункты меню, связанные с этой категорией
            MenuItem::where('type', 'category')
                ->where('linked_id', $category->id)
                ->get()
                ->each(function ($item) use ($oldSlug, $newSlug) {
                    // Если в URL был старый slug, обновляем его
                    if ($item->url && str_contains($item->url, $oldSlug)) {
                        $item->url = str_replace($oldSlug, $newSlug, $item->url);
                        $item->save();
                    }
                });
        }

        CategoryUpdated::dispatch($category);
    }

    /**
     * Обработка события перед удалением категории
     */
    public function deleting(Category $category): void
    {
        // Сохраняем категорию перед удалением для события
        $category->refresh();
    }

    /**
     * Обработка события после удаления категории
     */
    public function deleted(Category $category): void
    {
        CategoryDeleted::dispatch($category);
    }
}

