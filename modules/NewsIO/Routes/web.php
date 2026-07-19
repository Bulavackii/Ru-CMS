<?php

use Illuminate\Support\Facades\Route;
use Modules\NewsIO\Controllers\Admin\NewsIOController;

Route::middleware(['web','auth','admin'])
    ->prefix('admin/news-io')->name('admin.newsio.')
    ->group(function () {
        Route::get('/', [NewsIOController::class, 'index'])->name('index');
        Route::post('/export', [NewsIOController::class, 'export'])->name('export');
        Route::post('/import', [NewsIOController::class, 'import'])->name('import');
        Route::post('/dry-run', [NewsIOController::class, 'dryRun'])->name('dryrun');
    });
