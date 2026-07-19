<?php

use Illuminate\Support\Facades\Route;
use Modules\Comments\Controllers\Frontend\CommentController as FrontendCommentController;
use Modules\Comments\Controllers\Admin\CommentController as AdminCommentController;

// Публичные маршруты (API)
Route::prefix('api/comments')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', [FrontendCommentController::class, 'index'])->name('api.comments.index');
        Route::post('/', [FrontendCommentController::class, 'store'])->name('api.comments.store');
        Route::post('/{comment}/vote', [FrontendCommentController::class, 'vote'])->name('api.comments.vote');
    });

// Публичные маршруты (для обратной совместимости)
Route::prefix('comments')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', [FrontendCommentController::class, 'index'])->name('comments.index');
        Route::post('/', [FrontendCommentController::class, 'store'])->name('comments.store');
        Route::post('/{comment}/like', [FrontendCommentController::class, 'like'])->name('comments.like');
        Route::post('/{comment}/dislike', [FrontendCommentController::class, 'dislike'])->name('comments.dislike');
    });

// Админ-маршруты
Route::prefix('admin/comments')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.comments.')
    ->group(function () {
        Route::get('/', [AdminCommentController::class, 'index'])->name('index');
        Route::post('/{comment}/approve', [AdminCommentController::class, 'approve'])->name('approve');
        Route::post('/{comment}/reject', [AdminCommentController::class, 'reject'])->name('reject');
        Route::post('/{comment}/spam', [AdminCommentController::class, 'spam'])->name('spam');
        Route::delete('/{comment}', [AdminCommentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [AdminCommentController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/bulk', [AdminCommentController::class, 'bulkAction'])->name('bulk'); // для обратной совместимости
    });

