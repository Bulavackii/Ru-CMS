<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Controllers\Admin\CategoryController;

Route::prefix('admin/categories')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/create', [CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
});
