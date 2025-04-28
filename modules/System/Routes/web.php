<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Controllers\Admin\ModuleController;

// Группа маршрутов для админки
Route::prefix('admin/modules')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('admin.modules.index');
    });
