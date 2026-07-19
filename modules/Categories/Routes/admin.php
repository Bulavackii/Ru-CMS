<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Controllers\Admin\CategoryController;

/*
|--------------------------------------------------------------------------
| 📁 Маршруты модуля Categories (Админка)
|--------------------------------------------------------------------------
|
| Все маршруты защищены middleware:
| - web: базовые сессии, куки, CSRF и т.д.
| - auth: доступ только для авторизованных пользователей
| - admin: доступ только для администраторов (проверка через is_admin)
|
| Префикс: /admin/categories
| Именование: admin.categories.*
|
*/

Route::prefix('admin/categories')
    ->middleware(['web', 'auth', 'admin']) // 🔒 Защищённые маршруты
    ->name('admin.categories.')            // 🏷️ Префикс имени маршрута
    ->group(function () {

        // 📄 Список всех категорий
        Route::get('/', [CategoryController::class, 'index'])->name('index');

        // ➕ Форма создания категории
        Route::get('/create', [CategoryController::class, 'create'])->name('create');

        // 💾 Сохранение новой категории
        Route::post('/store', [CategoryController::class, 'store'])->name('store');

        // ✏️ Форма редактирования категории
        Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');

        // ♻️ Обновление категории
        Route::put('/{id}/update', [CategoryController::class, 'update'])->name('update');

        // 🗑️ Удаление категории
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');

        // 🗂️ Массовое удаление (если реализовано в контроллере)
        Route::delete('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulk-delete');
    });
