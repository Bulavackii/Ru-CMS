<?php

use Illuminate\Support\Facades\Route;
use Modules\Files\Controllers\Admin\FileController;

Route::prefix('admin/files')->middleware(['web', 'auth', 'admin'])->name('admin.files.')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::post('/upload', [FileController::class, 'upload'])->name('upload');
    // Строго ДО /{file}: иначе «browse» уедет в привязку модели как
    // идентификатор файла и вернёт 404 вместо списка.
    Route::get('/browse', [FileController::class, 'browse'])->name('browse');
    // По той же причине здесь, а не ниже. Имя совпадает с маршрутом из
    // routes/web.php, который ведёт в дубль контроллера в ядре: этот
    // регистрируется позже и перекрывает его.
    Route::delete('/bulk-delete', [FileController::class, 'bulkDelete'])->name('bulkDelete');
    Route::get('/{file}', [FileController::class, 'show'])->name('show');
    Route::put('/{file}', [FileController::class, 'update'])->name('update');
    Route::post('/{file}/crop', [FileController::class, 'crop'])->name('crop');
    Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});

// Своих категорий у медиатеки больше нет: файлы раскладываются по общим
// категориям проекта (раздел «Категории»). Группа admin.file-categories.*
// удалена — её index рендерил вьюху Files::admin.categories, которой в модуле
// не существует, то есть маршрут отдавал 500 на каждый заход.

