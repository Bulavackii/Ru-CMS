<?php

use Illuminate\Support\Facades\Route;
use Modules\Install\Controllers\InstallController;

/**
 * 🛠️ Маршруты модуля установки (Install)
 *
 * Эти маршруты доступны только до завершения установки.
 * После создания файла `storage/install.lock` — доступ будет закрыт.
 */

Route::middleware('web')->prefix('install')->group(function () {
    Route::middleware(function ($request, $next) {
        // ⛔ Блокировка доступа к /install, если CMS уже установлена
        if (file_exists(storage_path('install.lock'))) {
            abort(404, 'Установка уже завершена');
        }
        return $next($request);
    })->group(function () {
        Route::get('/', [InstallController::class, 'welcome'])->name('install.welcome');
        Route::get('/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
        Route::match(['get', 'post'], '/database', [InstallController::class, 'database'])->name('install.database');
        Route::match(['get', 'post'], '/admin', [InstallController::class, 'admin'])->name('install.admin');
        Route::get('/finish', [InstallController::class, 'finish'])->name('install.finish');
    });
});
