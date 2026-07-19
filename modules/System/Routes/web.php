<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Controllers\Admin\ModuleController;

// Основные маршруты модулей
Route::prefix('admin/modules')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('admin.modules.index');
        Route::post('/install', [ModuleController::class, 'install'])->name('admin.modules.install');
        Route::patch('/toggle/{id}', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
        Route::delete('/destroy/{id}', [ModuleController::class, 'destroy'])->name('admin.modules.destroy');
        Route::patch('/archive/{id}', [ModuleController::class, 'archive'])->name('admin.modules.archive');
        Route::get('/download-archive/{name}', [ModuleController::class, 'downloadArchive'])->name('admin.modules.downloadArchive');
        Route::post('/reorder', [ModuleController::class, 'reorder'])->name('admin.modules.reorder');
        Route::post('/generate-keys/{id}', [ModuleController::class, 'generateKeys'])->name('admin.modules.generateKeys');
        Route::get('/security-check/{id}', [ModuleController::class, 'securityCheck'])->name('admin.modules.securityCheck');
        Route::post('/bulk-toggle', [ModuleController::class, 'bulkToggle'])->name('admin.modules.bulkToggle');
        Route::post('/bulk-delete', [ModuleController::class, 'bulkDelete'])->name('admin.modules.bulkDelete');
    });

// Маршруты децентрализованной загрузки
require __DIR__ . '/distribution.php';
