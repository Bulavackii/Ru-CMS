<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Controllers\Frontend\NewsController as FrontendNewsController;

// Фронтовой маршрут для просмотра новости по слагу
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])
    ->name('news.show');

Route::prefix('admin/news')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('admin.news.index');
    Route::get('/create', [NewsController::class, 'create'])->name('admin.news.create');
    Route::post('/', [NewsController::class, 'store'])->name('admin.news.store');
    Route::get('/{news}/edit', [NewsController::class, 'edit'])->name('admin.news.edit');
    Route::put('/{news}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/{news}', [NewsController::class, 'destroy'])->name('admin.news.destroy');

    // 🔁 Групповые действия
    Route::post('/bulk', [NewsController::class, 'bulkAction'])->name('admin.news.bulk');
    Route::post('/bulk-update', [NewsController::class, 'bulkUpdate'])->name('admin.news.bulk.update');
    Route::get('/bulk', [NewsController::class, 'bulkEdit'])->name('admin.news.bulk.edit'); // ← вот это
});
