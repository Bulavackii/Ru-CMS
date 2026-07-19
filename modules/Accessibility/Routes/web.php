<?php

use Illuminate\Support\Facades\Route;
use Modules\Accessibility\Controllers\Admin\AccessibilityAdminController;

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/accessibility')
    ->name('admin.accessibility.')
    ->group(function () {
        Route::get('/', [AccessibilityAdminController::class, 'index'])->name('index');
        Route::post('/update', [AccessibilityAdminController::class, 'update'])->name('update');
    });
