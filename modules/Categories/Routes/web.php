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
        
        // Массовые действия
        Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulkDelete');
        Route::post('/bulk-update-type', [CategoryController::class, 'bulkUpdateType'])->name('bulk-update-type');
        Route::post('/bulk-update-parent', [CategoryController::class, 'bulkUpdateParent'])->name('bulk-update-parent');
        Route::post('/bulk-update-active', [CategoryController::class, 'bulkUpdateActive'])->name('bulk-update-active');
    });
