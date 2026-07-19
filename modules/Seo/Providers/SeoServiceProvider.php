<?php

namespace Modules\Seo\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Конфиг доступен как config('seo.*')
        $this->mergeConfigFrom(__DIR__ . '/../Config/seo.php', 'seo');

        // Сервис синхронизации
        $this->app->singleton(\Modules\Seo\Services\SeoSyncService::class, function () {
            return new \Modules\Seo\Services\SeoSyncService();
        });

        // По желанию — билдер карт для ручного вызова
        $this->app->singleton(\Modules\Seo\Services\SitemapBuilder::class, function () {
            return new \Modules\Seo\Services\SitemapBuilder();
        });
    }

    public function boot(): void
    {
        // --- Middleware: редиректы на всю группу 'web'
        $router = $this->app['router'];
        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('seo.redirects', \Modules\Seo\Middleware\RedirectMiddleware::class);
        }
        if (method_exists($router, 'pushMiddlewareToGroup')) {
            $router->pushMiddlewareToGroup('web', \Modules\Seo\Middleware\RedirectMiddleware::class);
        }

        // --- Роуты/вьюхи
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'seo');

        // --- Публикации (миграции модуля живут в единой database/migrations/)
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../Config/seo.php' => config_path('seo.php')], 'seo-config');
        }

        // --- Автосинк источников и push-back
        $this->registerAutosyncHooks();

        // --- Расписание задач
        $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('seo.features.metrica')) {
                $schedule->job(new \Modules\Seo\Jobs\PullMetrica())->hourlyAt(10)->name('seo:pull-metrica');
            }
            if (config('seo.features.webmaster')) {
                $schedule->job(new \Modules\Seo\Jobs\PullWebmasterStats())->dailyAt('04:00')->name('seo:pull-webmaster');
            }

            // Пересборка sitemap: если очереди синхронные — запускаем билдер напрямую
            $queueDriver = config('queue.default', config('queue.connection', 'sync'));
            if ($queueDriver === 'sync') {
                $schedule->call(function () {
                    try {
                        app(\Modules\Seo\Services\SitemapBuilder::class)->build();
                    } catch (\Throwable $e) {
                        // молча пропускаем, чтобы не падал планировщик
                    }
                })->dailyAt('03:00')->name('seo:build-sitemaps');
            } else {
                // иначе — через джобу
                $schedule->job(new \Modules\Seo\Jobs\BuildSitemaps())->dailyAt('03:00')->name('seo:build-sitemaps');
            }
        });
    }

    /**
     * Регистрируем хуки:
     * 1) Источники (News/Menu) → SEO (upsert)
     * 2) SEO (saved) → источники (push-back, без зацикливания)
     */
    protected function registerAutosyncHooks(): void
    {
        // Простейший guard от циклов (SEO saved → News saved → SEO saved ...)
        $isPushback = function (): bool {
            return app()->bound('seo.pushback') && app('seo.pushback') === true;
        };
        $beginPushback = function (): void {
            app()->instance('seo.pushback', true);
        };
        $endPushback = function (): void {
            if (method_exists(app(), 'forgetInstance')) {
                app()->forgetInstance('seo.pushback');
            } else {
                app()->instance('seo.pushback', false);
            }
        };

        /* ---------- Источники → SEO ---------- */

        // Новости
        if (class_exists(\Modules\News\Models\News::class)) {
            \Modules\News\Models\News::saved(function ($news) use ($isPushback) {
                // если сейчас выполняем push-back — пропускаем, чтобы не зациклиться
                if ($isPushback()) return;

                try {
                    app(\Modules\Seo\Services\SeoSyncService::class)->upsertFromNews($news);
                } catch (\Throwable $e) {
                    \Log::debug('SEO autosync(news) skipped: ' . $e->getMessage(), ['news_id' => $news->id ?? null]);
                }
            });

            \Modules\News\Models\News::deleted(function ($news) {
                try {
                    \Modules\Seo\Models\SeoPage::where([
                        'source_type' => 'news',
                        'source_id'   => $news->id ?? null,
                    ])->delete();
                } catch (\Throwable $e) {
                    \Log::debug('SEO autosync(news delete) skipped: ' . $e->getMessage(), ['news_id' => $news->id ?? null]);
                }
            });
        }

        // Страницы меню
        if (class_exists(\Modules\Menu\Models\Page::class)) {
            \Modules\Menu\Models\Page::saved(function ($page) use ($isPushback) {
                if ($isPushback()) return;

                try {
                    app(\Modules\Seo\Services\SeoSyncService::class)->upsertFromMenuPage($page);
                } catch (\Throwable $e) {
                    \Log::debug('SEO autosync(menu page) skipped: ' . $e->getMessage(), ['page_id' => $page->id ?? null]);
                }
            });

            \Modules\Menu\Models\Page::deleted(function ($page) {
                try {
                    \Modules\Seo\Models\SeoPage::where([
                        'source_type' => 'page',
                        'source_id'   => $page->id ?? null,
                    ])->delete();
                } catch (\Throwable $e) {
                    \Log::debug('SEO autosync(menu delete) skipped: ' . $e->getMessage(), ['page_id' => $page->id ?? null]);
                }
            });
        }

        /* ---------- SEO → Источники (push-back) ---------- */

        if (class_exists(\Modules\Seo\Models\SeoPage::class)) {
            \Modules\Seo\Models\SeoPage::saved(function (\Modules\Seo\Models\SeoPage $seo) use ($beginPushback, $endPushback) {
                try {
                    // работаем только если есть привязка к источнику и включено в конфиге
                    if (!$seo->source_type || !$seo->source_id) return;
                    if (!config('seo.features.push_back_to_sources', true)) return;

                    $beginPushback();

                    switch ($seo->source_type) {
                        case 'news':
                            $this->pushBackToNews($seo);
                            break;
                        case 'page':
                            // при необходимости — реализовать push-back в Menu/Page
                            break;
                    }
                } catch (\Throwable $e) {
                    \Log::debug('SEO push-back skipped: ' . $e->getMessage(), ['seo_id' => $seo->id ?? null]);
                } finally {
                    $endPushback();
                }
            });
        }
    }

    /**
     * Обновляем поля в новости из SEO без ломания схемы БД у разных проектов:
     * - meta title → в news.seo_title, иначе в news.meta_title (если есть)
     * - meta description → в news.seo_description, иначе в news.meta_description (если есть)
     * - менять news.title из seo.h1 ТОЛЬКО если включено seo.features.push_back_change_title
     */
    protected function pushBackToNews(\Modules\Seo\Models\SeoPage $seo): void
    {
        if (!class_exists(\Modules\News\Models\News::class)) {
            return;
        }

        $news = \Modules\News\Models\News::find($seo->source_id);
        if (!$news) return;

        $dirty = false;

        // meta title
        $metaTitleCol = Schema::hasColumn('news', 'seo_title')
            ? 'seo_title'
            : (Schema::hasColumn('news', 'meta_title') ? 'meta_title' : null);

        if ($metaTitleCol && !is_null($seo->title) && $news->{$metaTitleCol} !== $seo->title) {
            $news->{$metaTitleCol} = $seo->title;
            $dirty = true;
        }

        // meta description
        $metaDescCol = Schema::hasColumn('news', 'seo_description')
            ? 'seo_description'
            : (Schema::hasColumn('news', 'meta_description') ? 'meta_description' : null);

        if ($metaDescCol && !is_null($seo->description) && $news->{$metaDescCol} !== $seo->description) {
            $news->{$metaDescCol} = $seo->description;
            $dirty = true;
        }

        // менять основной заголовок новости из H1 — по фиче-флагу
        if (config('seo.features.push_back_change_title', false)) {
            if (!is_null($seo->h1) && Schema::hasColumn('news', 'title') && $news->title !== $seo->h1) {
                $news->title = $seo->h1;
                $dirty = true;
            }
        }

        if ($dirty) {
            $news->save();
        }
    }
}
