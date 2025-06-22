<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Controllers\Admin\CategoryController;

Route::prefix('admin/categories')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.categories.')
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/store', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('admin.categories.bulkDelete');
    });
