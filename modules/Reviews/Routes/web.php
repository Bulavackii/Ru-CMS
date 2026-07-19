<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Controllers\Admin\ReviewController as AdminReviewController;
use Modules\Reviews\Controllers\Frontend\ReviewController;

// Админские маршруты
Route::prefix('admin/reviews')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        Route::get('/', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
        Route::get('/{id}', [AdminReviewController::class, 'show'])->name('admin.reviews.show');
        Route::post('/{id}/moderate', [AdminReviewController::class, 'moderate'])->name('admin.reviews.moderate');
        Route::post('/{id}/reply', [AdminReviewController::class, 'reply'])->name('admin.reviews.reply');
        Route::get('/stats', [AdminReviewController::class, 'stats'])->name('admin.reviews.stats');
        Route::post('/bulk-moderate', [AdminReviewController::class, 'bulkModerate'])->name('admin.reviews.bulkModerate');
        Route::get('/export', [AdminReviewController::class, 'export'])->name('admin.reviews.export');
        Route::post('/import', [AdminReviewController::class, 'import'])->name('admin.reviews.import');
    });

// Публичные маршруты (API)
Route::prefix('api/reviews')
    ->middleware(['web'])
    ->group(function () {
        // Получение отзывов
        Route::get('/{itemType}/{itemId}', [ReviewController::class, 'getReviews'])
            ->name('api.reviews.get');

        // Статистика
        Route::get('/{itemType}/{itemId}/stats', [ReviewController::class, 'getStats'])
            ->name('api.reviews.stats');

        // Добавление отзыва
        Route::post('/{itemType}/{itemId}', [ReviewController::class, 'store'])
            ->name('api.reviews.store');

        // Проверка возможности добавления
        Route::get('/{itemType}/{itemId}/can-review', [ReviewController::class, 'canReview'])
            ->name('api.reviews.can');

        // Виджет
        Route::get('/widget', [ReviewController::class, 'widget'])
            ->name('api.reviews.widget');
    });

// Публичные формы (Blade)
Route::prefix('reviews')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/{itemType}/{itemId}/create', [ReviewController::class, 'createForm'])
            ->name('reviews.create');

        Route::post('/{itemType}/{itemId}', [ReviewController::class, 'submitForm'])
            ->name('reviews.submit');
    });
