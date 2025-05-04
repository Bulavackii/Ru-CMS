<?php

use Illuminate\Support\Facades\Route;
use Modules\Slideshow\Controllers\Admin\SlideshowController;

Route::prefix('admin/slideshow')->middleware(['web', 'auth', 'admin'])->group(function () {
    // 👉 Сначала — более специфичные маршруты
    Route::get('/{slideshow_id}/slides/create', [SlideshowController::class, 'create'])->name('admin.slides.create');
    Route::post('/slides', [SlideshowController::class, 'store'])->name('admin.slides.store');

    // ✅ Затем — маршруты для самого слайдшоу
    Route::get('/', [SlideshowController::class, 'index'])->name('admin.slideshow.index');
    Route::get('/create', [SlideshowController::class, 'createSlideshow'])->name('admin.slideshow.create');
    Route::post('/store', [SlideshowController::class, 'storeSlideshow'])->name('admin.slideshow.store');
    Route::get('/{id}/edit', [SlideshowController::class, 'edit'])->name('admin.slideshow.edit');
    Route::delete('/{slideshow}', [SlideshowController::class, 'destroy'])->name('admin.slideshow.destroy');
});

