<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\News\Models\News;

class OrderController extends Controller
{
    /**
     * 📦 Список заказов в админке
     */
    public function index()
    {
        // 🟢 Помечаем все новые заказы как просмотренные
        Order::where('is_new', true)->update(['is_new' => false]);

        // 🔄 Загружаем заказы с отношениями
        $orders = Order::with(['paymentMethod', 'deliveryMethod', 'items', 'user'])
            ->latest()
            ->paginate(15);

        // 📄 Отображаем представление
        return view('Payments::admin.orders.index', compact('orders'));
    }

    /**
     * 🔍 Просмотр конкретного заказа
     */
    public function show(Order $order)
    {
        $order->load(['items', 'paymentMethod', 'deliveryMethod', 'user']);

        return view('Payments::admin.orders.show', compact('order'));
    }

    /**
     * 📝 Создание нового заказа (из корзины или формы)
     */
    public function store(Request $request)
    {
        // ✅ Валидация данных
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|integer|exists:news,id',
            'items.*.qty'        => 'required|integer|min:1',
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'delivery_method_id' => 'nullable|exists:delivery_methods,id',
        ]);

        // 🔐 Транзакция: заказ и вычитание товаров
        DB::transaction(function () use ($request) {
            // 💾 Создаём заказ
            $order = Order::create([
                'user_id'           => auth()->id(),
                'status'            => 'new',
                'is_new'            => true,
                'payment_method_id' => $request->payment_method_id,
                'delivery_method_id'=> $request->delivery_method_id,
            ]);

            // 🧾 Добавляем товары
            foreach ($request->items as $item) {
                $product = News::findOrFail($item['id']);

                // ❗ Проверка доступного остатка
                if (!is_null($product->stock) && $product->stock < $item['qty']) {
                    throw new \Exception('Недостаточно товара на складе: ' . $product->title);
                }

                // 💽 Создание записи OrderItem
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'qty'        => $item['qty'],
                    'price'      => $product->price,
                ]);

                // 🧮 Обновляем остаток товара
                if (!is_null($product->stock)) {
                    $product->decrement('stock', $item['qty']);
                }
            }
        });

        // 🔁 Перенаправление с сообщением
        return redirect()
            ->route('dashboard.orders')
            ->with('success', 'Заказ успешно оформлен, остаток обновлён.');
    }
}
