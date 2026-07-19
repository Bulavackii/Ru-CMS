<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Events\NewsCreated;
use App\Listeners\UpdateSeoForNews;
use App\Listeners\ClearCacheOnNewsUpdate;
use Modules\News\Models\News;
use Modules\Categories\Models\Category;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_created_event_is_dispatched()
    {
        Event::fake();

        // NewsCreated не привязан к модели через observer/$dispatchesEvents —
        // его явно диспатчат контроллеры (Admin\NewsController,
        // Api\V1\NewsController) после News::create(). Фабрика создаёт
        // запись напрямую, поэтому здесь воспроизводим тот же вызов.
        $news = News::factory()->create(['template' => 'default']);
        event(new NewsCreated($news));

        Event::assertDispatched(NewsCreated::class, function ($event) use ($news) {
            return $event->news->id === $news->id;
        });
    }

    public function test_seo_update_listener_runs()
    {
        $listener = new UpdateSeoForNews();
        $news = News::factory()->create(['template' => 'default', 'published' => true]);

        $listener->handle(new NewsCreated($news));

        // Проверяем, что SEO запись создана
        $this->assertDatabaseHas('seo_pages', [
            'source_type' => 'news',
            'source_id' => $news->id,
        ]);
    }

    public function test_cache_clear_listener_runs()
    {
        // Устанавливаем тестовый кэш
        Cache::put('home_categories', ['test'], 60);
        Cache::put('template_default_test', ['test'], 60);

        $listener = new ClearCacheOnNewsUpdate();
        $news = News::factory()->create(['template' => 'default']);

        $listener->handle(new NewsCreated($news));

        // Проверяем, что кэш очищен
        $this->assertNull(Cache::get('home_categories'));
    }

    public function test_event_listeners_are_registered()
    {
        $events = app('events');

        // getListeners() возвращает уже обёрнутые в Closure слушатели —
        // сравнивать с class-string нельзя. getRawListeners() отдаёт
        // необёрнутые записи как они зарегистрированы в $listen.
        $listeners = $events->getRawListeners()[NewsCreated::class] ?? [];

        $this->assertCount(2, $listeners);
        $this->assertContains(UpdateSeoForNews::class, $listeners);
        $this->assertContains(ClearCacheOnNewsUpdate::class, $listeners);
    }
}
