<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Controllers\Admin\NotificationController;

Route::middleware(['web', 'auth', 'admin'])->prefix('admin/notifications')->name('admin.notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/create', [NotificationController::class, 'create'])->name('create');
    Route::post('/', [NotificationController::class, 'store'])->name('store');
    Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
    Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::patch('/{notification}/toggle', [NotificationController::class, 'toggle'])->name('toggle');
});
