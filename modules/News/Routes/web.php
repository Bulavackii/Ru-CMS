<?php

use Illuminate\Support\Facades\Route;

// ======================
// Админка
// ======================
use Modules\News\Controllers\Admin\NewsController as AdminNewsController;

Route::prefix('admin/news')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/',               [AdminNewsController::class, 'index'])->name('admin.news.index');
    Route::get('/create',         [AdminNewsController::class, 'create'])->name('admin.news.create');
    Route::post('/',              [AdminNewsController::class, 'store'])->name('admin.news.store');
    Route::get('/{news}/edit',    [AdminNewsController::class, 'edit'])->name('admin.news.edit');
    Route::put('/{news}',         [AdminNewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/{news}',      [AdminNewsController::class, 'destroy'])->name('admin.news.destroy');

    // Групповые действия
    Route::post('/bulk',          [AdminNewsController::class, 'bulkAction'])->name('admin.news.bulk');
    Route::post('/bulk-update',   [AdminNewsController::class, 'bulkUpdate'])->name('admin.news.bulk.update');
    Route::get('/bulk',           [AdminNewsController::class, 'bulkEdit'])->name('admin.news.bulk.edit');
});

// ======================
// Публичные страницы
// ======================
use Modules\News\Controllers\Frontend\NewsController as FrontNewsController;

Route::middleware(['web'])->group(function () {
    Route::get('/news',            [FrontNewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}',     [FrontNewsController::class, 'show'])->name('news.show');
});
