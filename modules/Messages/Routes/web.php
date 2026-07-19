<?php

use Illuminate\Support\Facades\Route;
use Modules\Messages\Controllers\Admin\MessageController;

/*
|--------------------------------------------------------------------------
| 📬 Маршруты модуля "Messages" (Админка)
|--------------------------------------------------------------------------
|
| Эти маршруты доступны только администраторам и предназначены
| для управления внутренними сообщениями между админами.
|
*/

Route::prefix('admin/messages')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        // 📄 Список всех сообщений (входящие и исходящие)
        Route::get('/', [MessageController::class, 'index'])->name('admin.messages.index');

        // 📝 Форма создания нового сообщения
        Route::get('/create', [MessageController::class, 'create'])->name('admin.messages.create');

        // 📝 Форма ответа на сообщение
        Route::get('/{message}/reply', [MessageController::class, 'reply'])->name('admin.messages.reply');

        // 💾 Отправка сообщения (POST)
        Route::post('/', [MessageController::class, 'store'])->name('admin.messages.store');

        // 📦 Массовые операции
        Route::post('/bulk-action', [MessageController::class, 'bulkAction'])->name('admin.messages.bulk');

        // 📬 Просмотр конкретного сообщения
        Route::get('/{message}', [MessageController::class, 'show'])->name('admin.messages.show');

        // 🗑️ Удаление сообщения
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('admin.messages.destroy');

        // ⭐ Пометить как важное/неважное
        Route::post('/{message}/toggle-important', [MessageController::class, 'toggleImportant'])->name('admin.messages.toggle-important');

        // 📬 Пометить как прочитанное/непрочитанное
        Route::post('/{message}/toggle-read', [MessageController::class, 'toggleRead'])->name('admin.messages.toggle-read');

        // 📦 Архивирование
        Route::post('/{message}/archive', [MessageController::class, 'archive'])->name('admin.messages.archive');

        // 📥 Скачивание вложения
        Route::get('/attachments/{attachment}/download', [MessageController::class, 'downloadAttachment'])->name('admin.messages.attachment.download');
    });
