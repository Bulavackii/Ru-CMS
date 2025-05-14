<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Controllers\Admin\DeliveryMethodController;

Route::prefix('admin/delivery')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [DeliveryMethodController::class, 'index'])->name('admin.delivery.index');
    Route::get('/create', [DeliveryMethodController::class, 'create'])->name('admin.delivery.create');
    Route::post('/', [DeliveryMethodController::class, 'store'])->name('admin.delivery.store');
    Route::get('/{delivery}/edit', [DeliveryMethodController::class, 'edit'])->name('admin.delivery.edit');
    Route::put('/{delivery}', [DeliveryMethodController::class, 'update'])->name('admin.delivery.update');
    Route::delete('/{delivery}', [DeliveryMethodController::class, 'destroy'])->name('admin.delivery.destroy');
});
