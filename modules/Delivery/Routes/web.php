<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Controllers\Admin\DeliveryMethodController;
use Modules\Delivery\Controllers\Api\DeliveryApiController;

// Админ-маршруты
Route::prefix('admin/delivery')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [DeliveryMethodController::class, 'index'])->name('admin.delivery.index');
    Route::get('/create', [DeliveryMethodController::class, 'create'])->name('admin.delivery.create');
    Route::post('/', [DeliveryMethodController::class, 'store'])->name('admin.delivery.store');
    Route::get('/{delivery}/edit', [DeliveryMethodController::class, 'edit'])->name('admin.delivery.edit');
    Route::put('/{delivery}', [DeliveryMethodController::class, 'update'])->name('admin.delivery.update');
    Route::delete('/{delivery}', [DeliveryMethodController::class, 'destroy'])->name('admin.delivery.destroy');
});

// API маршруты для фронтенда
Route::prefix('api/delivery')->middleware(['web'])->group(function () {
    Route::post('/calculate', [DeliveryApiController::class, 'calculate'])->name('api.delivery.calculate');
    Route::get('/pickup-points', [DeliveryApiController::class, 'pickupPoints'])->name('api.delivery.pickup-points');
    Route::get('/available-methods', [DeliveryApiController::class, 'availableMethods'])->name('api.delivery.available-methods');
});
