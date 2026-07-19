<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\Controllers\Admin\SeoController as AdminSeo;
use Modules\Seo\Controllers\Admin\PagesController as AdminPages;
use Modules\Seo\Controllers\Admin\RedirectsController as AdminRedirects;
use Modules\Seo\Controllers\Admin\RobotsController as AdminRobots;
use Modules\Seo\Controllers\Admin\SitemapController as AdminSitemaps;
use Modules\Seo\Controllers\Frontend\RobotsController as FrontRobots;
use Modules\Seo\Controllers\Frontend\SitemapController as FrontSitemaps;

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth'])
    ->prefix('admin/seo')
    ->as('seo.')
    ->group(function () {
        Route::get('/', [AdminSeo::class, 'index'])->name('index');

        // Pages
        Route::get('/pages',        [AdminPages::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [AdminPages::class, 'create'])->name('pages.create');
        Route::post('/pages',       [AdminPages::class, 'store'])->name('pages.store');
        Route::post('/pages/bulk',  [AdminPages::class, 'bulkAction'])->name('pages.bulk');

        // Sync all
        Route::post('/pages/sync',  [AdminPages::class, 'sync'])->name('pages.sync');

        // Refresh one
        Route::post('/pages/{id}/refresh', [AdminPages::class, 'refresh'])
            ->whereNumber('id')
            ->name('pages.refresh');

        Route::get('/pages/{id}/edit', [AdminPages::class, 'edit'])
            ->whereNumber('id')
            ->name('pages.edit');

        Route::put('/pages/{id}', [AdminPages::class, 'update'])
            ->whereNumber('id')
            ->name('pages.update');

        Route::delete('/pages/{id}', [AdminPages::class, 'destroy'])
            ->whereNumber('id')
            ->name('pages.destroy');

        // Lock / Unlock
        Route::post('/pages/{id}/lock',   [AdminPages::class, 'lock'])
            ->whereNumber('id')
            ->name('pages.lock');
        Route::post('/pages/{id}/unlock', [AdminPages::class, 'unlock'])
            ->whereNumber('id')
            ->name('pages.unlock');

        // Redirects
        Route::get('/redirects',        [AdminRedirects::class, 'index'])->name('redirects.index');
        Route::get('/redirects/create', [AdminRedirects::class, 'create'])->name('redirects.create');
        Route::post('/redirects',       [AdminRedirects::class, 'store'])->name('redirects.store');
        Route::post('/redirects/bulk',  [AdminRedirects::class, 'bulkAction'])->name('redirects.bulk');
        Route::get('/redirects/{id}/edit', [AdminRedirects::class, 'edit'])
            ->whereNumber('id')
            ->name('redirects.edit');
        Route::put('/redirects/{id}',     [AdminRedirects::class, 'update'])
            ->whereNumber('id')
            ->name('redirects.update');
        Route::delete('/redirects/{id}',  [AdminRedirects::class, 'destroy'])
            ->whereNumber('id')
            ->name('redirects.destroy');

        // robots.txt editor
        Route::get('/robots',  [AdminRobots::class, 'edit'])->name('robots.edit');
        Route::post('/robots', [AdminRobots::class, 'update'])->name('robots.update');

        // Sitemaps
        Route::get('/sitemaps',          [AdminSitemaps::class, 'index'])->name('sitemaps.index');
        Route::post('/sitemaps/rebuild', [AdminSitemaps::class, 'rebuild'])->name('sitemaps.rebuild');

        // Save from embedded SEO widget (optional)
        if (class_exists(\Modules\Seo\Controllers\Admin\EmbedController::class)) {
            Route::post('/embed/save', [\Modules\Seo\Controllers\Admin\EmbedController::class, 'save'])
                ->name('embed.save');
        }
    });

/*
|--------------------------------------------------------------------------
| FRONTEND
|--------------------------------------------------------------------------
*/
Route::middleware('web')->group(function () {
    Route::get('/sitemap.xml', [FrontSitemaps::class, 'xml'])->name('seo.sitemap.xml');
    Route::get('/robots.txt',  [FrontRobots::class,  'txt'])->name('seo.robots.txt');

    if (config('seo.features.news_sitemap')) {
        Route::get('/news-sitemap.xml', [FrontSitemaps::class, 'news'])->name('seo.sitemap.news');
    }
    if (config('seo.features.images_sitemap')) {
        Route::get('/images-sitemap.xml', [FrontSitemaps::class, 'images'])->name('seo.sitemap.images');
    }
});
