<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\Admin\PaymentController;
use Modules\Payments\Controllers\Admin\OrderController;
use Modules\Payments\Controllers\Frontend\CartController;

// 🛠️ Админка: управление способами оплаты
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/payments')
    ->name('admin.payments.')
    ->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');       // 📄 Список методов
        Route::get('/create', [PaymentController::class, 'create'])->name('create'); // ➕ Добавить
        Route::post('/', [PaymentController::class, 'store'])->name('store');        // 💾 Сохранить
        Route::get('/{id}/edit', [PaymentController::class, 'edit'])->name('edit');  // ✏️ Редактировать
        Route::put('/{id}', [PaymentController::class, 'update'])->name('update');   // 🔄 Обновить
        Route::delete('/{id}', [PaymentController::class, 'destroy'])->name('destroy'); // ❌ Удалить
    });

// 📦 Админка: управление заказами
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/orders')
    ->name('admin.orders.')
    ->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');           // 📄 Список заказов
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');      // 🔍 Просмотр заказа
    });

// 🛒 Клиент: корзина и оформление заказа
Route::middleware(['web'])->group(function () {
    // 📥 Просмотр корзины
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

    // ➕ Добавление товара в корзину (AJAX)
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');

    // ❌ Удаление товара из корзины
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

    // 💳 Оформление заказа
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    // ✅ Подтверждение оформления заказа
    Route::get('/cart/confirm/{id}', [CartController::class, 'confirm'])->name('cart.confirm');

    // ♻️ Обновление количества (опционально)
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

    Route::post('/cart/check-stock', [\Modules\Payments\Controllers\Frontend\CartController::class, 'checkStock'])->name('cart.checkStock');

    // 🔢 Получение количества товаров в корзине (AJAX для хедера)
    Route::get('/cart/count', function () {
        return response()->json([
            'count' => array_sum(array_column(session('cart', []), 'qty')),
        ]);
    })->name('cart.count');

    // 📦 Получение актуального остатка товара по ID (AJAX)
    Route::get('/product/{id}/stock', function ($id) {
        $product = \Modules\News\Models\News::findOrFail($id);
        return response()->json(['stock' => $product->stock ?? 0]);
    });
});
