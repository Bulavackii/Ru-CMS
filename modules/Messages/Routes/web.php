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

        // 💾 Отправка сообщения (POST)
        Route::post('/', [MessageController::class, 'store'])->name('admin.messages.store');

        // 📬 Просмотр конкретного сообщения
        Route::get('/{message}', [MessageController::class, 'show'])->name('admin.messages.show');
    });
