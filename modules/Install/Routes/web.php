<?php

use Illuminate\Support\Facades\Route;
use Modules\Install\Controllers\InstallController;

/**
 * 🛠️ Маршруты модуля установки (Install)
 *
 * Эти маршруты доступны только до завершения установки.
 * После создания файла `storage/install.lock` — доступ будет закрыт.
 */

Route::middleware(['web', 'skip.install.db', 'block.if.installed'])->prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('install.welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
    Route::get('/features', [InstallController::class, 'features'])->name('install.features');
    Route::match(['get', 'post'], '/database', [InstallController::class, 'database'])->name('install.database');
    Route::match(['get', 'post'], '/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::match(['get', 'post'], '/smtp', [InstallController::class, 'smtp'])->name('install.smtp');
    Route::match(['get', 'post'], '/license', [InstallController::class, 'license'])->name('install.license');
    Route::get('/finish', [InstallController::class, 'finish'])->name('install.finish');
});

/**
 * Итог установки живёт ОТДЕЛЬНО и намеренно без `block.if.installed`.
 *
 * К моменту показа файл-замок уже создан, и внутри группы выше эта
 * страница была бы недостижима: middleware увёл бы на форму входа, а
 * та — вошедшего админа в личный кабинет. Именно так финальный экран и
 * терялся, если тяжёлый запрос /install/finish обрывался.
 *
 * Доступ ограничен не замком, а сводкой в сессии: показать итог можно
 * только тому браузеру, который эту установку и проводил.
 */
Route::middleware(['web', 'skip.install.db'])->prefix('install')->group(function () {
    Route::get('/done', [InstallController::class, 'done'])->name('install.done');
});
