<?php

namespace App\Observers;

use Modules\Menu\Models\Page;
use Modules\Menu\Models\MenuItem;

/**
 * Observer для модели Page (модуль Menu)
 * Автоматически обновляет URL в пунктах меню при изменении slug страницы
 */
class PageObserver
{
    /**
     * Обработка события после обновления страницы
     */
    public function updated(Page $page): void
    {
        // Если изменился slug, обновляем URL в пунктах меню
        if ($page->wasChanged('slug')) {
            $oldSlug = $page->getOriginal('slug');
            $newSlug = $page->slug;

            // Находим все пункты меню, связанные с этой страницей
            MenuItem::where('type', 'page')
                ->where('linked_id', $page->id)
                ->get()
                ->each(function ($item) use ($oldSlug, $newSlug) {
                    // Если в URL был старый slug, обновляем его
                    if ($item->url && str_contains($item->url, $oldSlug)) {
                        $item->url = str_replace($oldSlug, $newSlug, $item->url);
                        $item->save();
                    }
                });
        }
    }
}




