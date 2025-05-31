<?php

use Illuminate\Support\Facades\Route;
use Modules\Slideshow\Controllers\Admin\SlideshowController;

// 🛠️ Административные маршруты для модуля "Слайдшоу"
Route::prefix('admin/slideshow')->middleware(['web', 'auth', 'admin'])->group(function () {

    // 🎞️ Управление отдельными слайдами
    Route::get('/{slideshow_id}/slides/create', [SlideshowController::class, 'create'])
        ->name('admin.slides.create'); // ➕ Форма добавления слайда

    Route::post('/slides', [SlideshowController::class, 'store'])
        ->name('admin.slides.store'); // 💾 Сохранение нового слайда

    Route::delete('/slides/{id}', [SlideshowController::class, 'deleteSlide'])
        ->name('admin.slides.destroy'); // ❌ Удаление слайда

    Route::post('/slides/sort', [SlideshowController::class, 'sort'])
        ->name('admin.slides.sort'); // 🔃 Сортировка слайдов (drag-n-drop)

    // 🖼️ Управление слайдшоу
    Route::get('/', [SlideshowController::class, 'index'])
        ->name('admin.slideshow.index'); // 📄 Список всех слайдшоу

    Route::get('/create', [SlideshowController::class, 'createSlideshow'])
        ->name('admin.slideshow.create'); // ➕ Создание слайдшоу

    Route::post('/store', [SlideshowController::class, 'storeSlideshow'])
        ->name('admin.slideshow.store'); // 💾 Сохранение слайдшоу

    Route::get('/{id}/edit', [SlideshowController::class, 'edit'])
        ->name('admin.slideshow.edit'); // ✏️ Редактирование слайдшоу и слайдов

    Route::delete('/{slideshow}', [SlideshowController::class, 'destroy'])
        ->name('admin.slideshow.destroy'); // ❌ Полное удаление слайдшоу

    Route::post('/slides/{id}/update', [SlideshowController::class, 'updateSlide'])
        ->name('admin.slides.update');
});
