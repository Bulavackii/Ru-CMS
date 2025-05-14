<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\Admin\PaymentController;
use Modules\Payments\Controllers\Admin\OrderController;
use Modules\Payments\Controllers\Frontend\CartController;

// 📦 Админка — платежные методы
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/payments')->name('admin.payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [PaymentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/{id}', [PaymentController::class, 'destroy'])->name('destroy');
});

// 📦 Админка — заказы
Route::prefix('admin/orders')->name('admin.orders.')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
});

// 🛒 Клиентская часть — корзина
Route::middleware(['web'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
});
