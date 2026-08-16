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
        Route::post('/{id}/check', [PaymentController::class, 'check'])->name('check'); // 🔌 Проверка связи с платёжной системой
    });

// 📦 Админка: управление заказами
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/orders')
    ->name('admin.orders.')
    ->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');           // 📄 Список заказов

        // Заведение заказа руками (по телефону, в переписке). Метод store()
        // существовал и раньше, но маршрута к нему не было вовсе — завести
        // заказ из панели было НЕЧЕМ. Объявлены ДО `/{order}`: слово
        // «create» иначе уехало бы в параметр (та же ловушка, что с
        // `reviews/stats`), и хотя `whereNumber` тут прикрывает, порядок
        // держим правильным.
        Route::get('/create', [OrderController::class, 'create'])->name('create');   // ➕ Форма
        Route::post('/', [OrderController::class, 'store'])->name('store');          // 💾 Сохранить

        Route::get('/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('show');      // 🔍 Просмотр заказа
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->whereNumber('order')->name('update.status'); // 🔄 Обновление статуса
        Route::delete('/{order}', [OrderController::class, 'destroy'])->whereNumber('order')->name('destroy'); // 🗑️ Удаление заказа
        Route::get('/export/csv', [OrderController::class, 'export'])->name('export'); // 📥 Экспорт в CSV
        Route::get('/stats', [OrderController::class, 'stats'])->name('stats');       // 📊 Статистика
    });

// 💳 Платежи
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/orders/{order}/payment/initiate', [OrderController::class, 'initiatePayment'])->name('orders.payment.initiate');
    Route::get('/payments/sbp/qr/{order}', function ($order) {
        // Показ QR-кода для СБП
        return view('Payments::public.sbp-qr', ['order' => \Modules\Payments\Models\Order::findOrFail($order)]);
    })->name('payments.sbp.qr');
});

// ↩️ Возврат покупателя с платёжной страницы. БЕЗ auth: заказ можно
// оформить гостем, и раньше он после оплаты попадал на форму входа.
// Статус берётся из заказа, а не объявляется по факту перехода: успех
// подтверждает webhook, а по этой ссылке можно просто вернуться «назад».
Route::middleware(['web'])
    ->withoutMiddleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/payments/success/{order}', [OrderController::class, 'paymentReturn'])->name('payments.success');
        Route::get('/payments/fail/{order}', [OrderController::class, 'paymentReturn'])->name('payments.fail');
    });

// 🌐 Webhook для платежных систем (публичный доступ, без CSRF)
Route::post('/payment/webhook/{gateway}', [OrderController::class, 'webhook'])
    ->name('payment.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, 'auth', 'admin']);

// � Клиент: корзина и оформление заказа
// Корзина и остатки — публичные: покупатель не обязан быть
// зарегистрирован. auth/admin снимаем явно, потому что маршруты модуля
// наследуют разный набор middleware в зависимости от того, как модуль
// загружен (активен в таблице modules или через loadLegacyModules).
Route::middleware(['web'])
    ->withoutMiddleware(['auth', 'admin'])
    ->group(function () {
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

    // ♻️ Обновление количества (AJAX)
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

    // 📦 Проверка остатка товара (AJAX)
    Route::post('/cart/check-stock', [CartController::class, 'checkStock'])->name('cart.checkStock');

    // 🔢 Получение количества товаров в корзине (AJAX для хедера)
    Route::get('/cart/count', function () {
        return response()->json([
            'count' => array_sum(array_column(session('cart', []), 'qty')),
        ]);
    })->name('cart.count');

    // 📦 Получение актуального остатка товара по ID (AJAX)
    // Из складского остатка вычитается то, что покупатель уже положил в
    // корзину. Раньше отдавалось «сырое» число из базы, и после добавления
    // в корзину счётчик в карточке не менялся: корзина склад не трогает,
    // она списывается только при оформлении. Покупатель мог набрать больше,
    // чем есть, и узнать об этом уже на оформлении заказа.
    //
    // Главная считает так же (HomeController), поэтому число совпадает и
    // после перезагрузки страницы.
    Route::get('/product/{id}/stock', function ($id) {
        $product = \Modules\News\Models\News::findOrFail($id);

        if (is_null($product->stock)) {
            // Остаток не задан — товар считается всегда доступным.
            return response()->json(['stock' => null]);
        }

        $inCart = (int) (session('cart', [])[$id]['qty'] ?? 0);

        return response()->json([
            'stock' => max((int) $product->stock - $inCart, 0),
        ]);
    })->name('product.stock');
});
