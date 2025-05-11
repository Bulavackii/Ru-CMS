<?php

use Illuminate\Support\Facades\Route;
use Modules\Messages\Controllers\Admin\MessageController;

Route::prefix('admin/messages')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('admin.messages.index');
    Route::get('/create', [MessageController::class, 'create'])->name('admin.messages.create');
    Route::post('/', [MessageController::class, 'store'])->name('admin.messages.store');
    Route::get('/{message}', [MessageController::class, 'show'])->name('admin.messages.show');
});
