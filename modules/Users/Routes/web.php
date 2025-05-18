<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\Admin\UserController;

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/admin/users/{user}/password', [UserController::class, 'editPassword'])->name('admin.users.password.edit');
    Route::put('/admin/users/{user}/password', [UserController::class, 'updatePassword'])->name('admin.users.password.update');
    Route::get('/admin/users/search/ajax', [UserController::class, 'ajaxSearch'])->name('admin.users.ajaxSearch');
});
