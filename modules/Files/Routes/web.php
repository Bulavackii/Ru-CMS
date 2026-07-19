<?php

use Illuminate\Support\Facades\Route;
use Modules\Files\Controllers\Admin\FileController;
use Modules\Files\Controllers\Admin\FileCategoryController;

Route::prefix('admin/files')->middleware(['web', 'auth', 'admin'])->name('admin.files.')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::post('/upload', [FileController::class, 'upload'])->name('upload');
    Route::get('/{file}', [FileController::class, 'show'])->name('show');
    Route::put('/{file}', [FileController::class, 'update'])->name('update');
    Route::post('/{file}/crop', [FileController::class, 'crop'])->name('crop');
    Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});

// Маршруты для категорий файлов
Route::prefix('admin/file-categories')->middleware(['web', 'auth', 'admin'])->name('admin.file-categories.')->group(function () {
    Route::get('/', [FileCategoryController::class, 'index'])->name('index');
    Route::post('/', [FileCategoryController::class, 'store'])->name('store');
    Route::put('/{category}', [FileCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [FileCategoryController::class, 'destroy'])->name('destroy');
});

