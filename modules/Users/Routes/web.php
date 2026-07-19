<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\Admin\UserController;

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/users/{user}/password', [UserController::class, 'editPassword'])->name('admin.users.password.edit');
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('admin.users.password.update');
    Route::get('/users/{user}/login-history', [UserController::class, 'loginHistory'])->name('admin.users.loginHistory');
    Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('admin.users.bulkAction');
    Route::get('/users/search/ajax', [UserController::class, 'ajaxSearch'])->name('admin.users.ajaxSearch');
});
