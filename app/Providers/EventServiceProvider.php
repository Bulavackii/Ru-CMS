<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\NewsCreated;
use App\Events\NewsUpdated;
use App\Events\NewsDeleted;
use App\Events\NotificationCreated;
use App\Events\NotificationUpdated;
use App\Events\NotificationDeleted;
use App\Events\SeoPageCreated;
use App\Events\SeoPageUpdated;
use App\Events\SeoPageDeleted;
use App\Events\RedirectRuleCreated;
use App\Events\RedirectRuleUpdated;
use App\Events\RedirectRuleDeleted;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\CategoryCreated;
use App\Events\CategoryUpdated;
use App\Events\CategoryDeleted;
use App\Listeners\UpdateSeoForNews;
use App\Listeners\ClearCacheOnNewsUpdate;
use App\Listeners\SendOrderCreatedNotifications;
use App\Listeners\SendOrderStatusChangedNotifications;
use App\Listeners\ClearCacheOnCategoryUpdate;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        NewsCreated::class => [
            UpdateSeoForNews::class,
            ClearCacheOnNewsUpdate::class,
        ],
        NewsUpdated::class => [
            UpdateSeoForNews::class,
            ClearCacheOnNewsUpdate::class,
        ],
        NewsDeleted::class => [
            ClearCacheOnNewsUpdate::class,
        ],
        NotificationCreated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        NotificationUpdated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        NotificationDeleted::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        SeoPageCreated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        SeoPageUpdated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        SeoPageDeleted::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        RedirectRuleCreated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        RedirectRuleUpdated::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        RedirectRuleDeleted::class => [
            // Можно добавить слушатели для очистки кэша
        ],
        OrderCreated::class => [
            SendOrderCreatedNotifications::class,
        ],
        OrderStatusChanged::class => [
            SendOrderStatusChangedNotifications::class,
        ],
        CategoryCreated::class => [
            ClearCacheOnCategoryUpdate::class,
        ],
        CategoryUpdated::class => [
            ClearCacheOnCategoryUpdate::class,
        ],
        CategoryDeleted::class => [
            ClearCacheOnCategoryUpdate::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
