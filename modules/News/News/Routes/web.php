<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Controllers\Admin\NewsController;

Route::middleware(['web', 'auth', 'admin'])->prefix('admin/news')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('admin.news.index');
    Route::get('/create', [NewsController::class, 'create'])->name('admin.news.create');
    Route::post('/', [NewsController::class, 'store'])->name('admin.news.store');
    Route::get('/{news}/edit', [NewsController::class, 'edit'])->name('admin.news.edit');
    Route::put('/{news}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/{news}', [NewsController::class, 'destroy'])->name('admin.news.destroy');
});
