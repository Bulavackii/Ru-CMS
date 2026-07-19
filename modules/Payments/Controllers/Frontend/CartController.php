<?php

namespace Modules\Payments\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = session('cart', []);
        $paymentMethods = PaymentMethod::where('active', true)->get();
        $deliveryMethods = DeliveryMethod::where('active', true)->get();

        return view('Payments::public.cart', compact('cart', 'paymentMethods', 'deliveryMethods'));
    }

    public function add(Request $request)
    {
        $id = $request->input('id');
        $qty = intval($request->input('qty'));

        $product = News::findOrFail($id);

        if (!is_null($product->stock) && $product->stock < $qty) {
            return response()->json([
                'message' => 'Недостаточно товара на складе. Доступно: ' . $product->stock
            ], 400);
        }

        $cart = session('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['qty'] += $qty;

            if (!is_null($product->stock) && $cart[$id]['qty'] > $product->stock) {
                return response()->json([
                    'message' => 'Вы не можете добавить больше товаров, чем есть на складе. Доступно: ' . $product->stock
                ], 400);
            }
        } else {
            $cart[$id] = [
                'id'    => $id,
                'title' => $request->input('title'),
                'price' => floatval($request->input('price')),
                'qty'   => $qty,
            ];
        }

        session(['cart' => $cart]);

        return response()->json(['message' => 'Добавлено в корзину']);
    }

    public function remove(Request $request)
    {
        $cart = session('cart', []);
        $id = $request->input('id');

        unset($cart[$id]);

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Товар удалён из корзины');
    }

    /**
     * 🔄 Обновление количества товара в корзине
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:news,id',
            'qty' => 'required|integer|min:1',
        ]);

        $id = $request->input('id');
        $qty = $request->input('qty');

        $cart = session('cart', []);

        if (!isset($cart[$id])) {
            return response()->json(['message' => 'Товар не найден в корзине'], 404);
        }

        $product = News::findOrFail($id);

        // Проверка остатка
        if (!is_null($product->stock) && $qty > $product->stock) {
            return response()->json([
                'message' => 'Недостаточно товара на складе. Доступно: ' . $product->stock
            ], 400);
        }

        $cart[$id]['qty'] = $qty;
        session(['cart' => $cart]);

        return response()->json([
            'message' => 'Количество обновлено',
            'subtotal' => $qty * $cart[$id]['price'],
            'total' => array_sum(array_map(fn($item) => $item['qty'] * $item['price'], $cart))
        ]);
    }

    /**
     * 📦 Проверка остатка товара (AJAX)
     */
    public function checkStock(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:news,id',
        ]);

        $product = News::findOrFail($request->input('id'));

        return response()->json([
            'stock' => $product->stock ?? 0,
            'available' => is_null($product->stock) || $product->stock > 0
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'delivery_method_id' => 'required|exists:delivery_methods,id',
        ]);

        $items = $request->input('items', []);

        if (empty($items)) {
            return redirect()->route('cart.index')->with('error', 'Корзина пуста');
        }

        // Получаем методы оплаты и доставки для проверок
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        $deliveryMethod = DeliveryMethod::find($request->delivery_method_id);

        if (!$paymentMethod || !$paymentMethod->active) {
            return redirect()->route('cart.index')->with('error', 'Выбранный метод оплаты недоступен');
        }

        if (!$deliveryMethod || !$deliveryMethod->active) {
            return redirect()->route('cart.index')->with('error', 'Выбранный метод доставки недоступен');
        }

        // Расчет общей суммы товаров
        $itemsTotal = collect($items)->sum(fn($item) => $item['qty'] * $item['price']);

        // Проверка ограничений сумм для платежной системы
        if ($paymentMethod->min_amount && $itemsTotal < $paymentMethod->min_amount) {
            return redirect()->route('cart.index')->with('error',
                "Минимальная сумма заказа для {$paymentMethod->title}: {$paymentMethod->min_amount} ₽");
        }

        if ($paymentMethod->max_amount && $itemsTotal > $paymentMethod->max_amount) {
            return redirect()->route('cart.index')->with('error',
                "Максимальная сумма заказа для {$paymentMethod->title}: {$paymentMethod->max_amount} ₽");
        }

        // Расчет комиссии
        $commissionAmount = 0;
        if ($paymentMethod->commission) {
            $commissionAmount = $itemsTotal * ($paymentMethod->commission / 100);
        }

        // Итоговая сумма с доставкой и комиссией
        $total = $itemsTotal + $deliveryMethod->price + $commissionAmount;

        try {
            $order = null;

            // Создаем заказ вне транзакции, чтобы избежать проблем с областью видимости
            DB::transaction(function () use ($request, $items, $paymentMethod, $deliveryMethod, $itemsTotal, $commissionAmount, $total) {
                $order = Order::create([
                    'user_id'            => Auth::check() ? Auth::id() : null,
                    'payment_method_id'  => $request->payment_method_id,
                    'delivery_method_id' => $request->delivery_method_id,
                    'total'              => $total,
                    'items_total'        => $itemsTotal,
                    'delivery_price'     => $deliveryMethod->price,
                    'commission'         => $commissionAmount,
                    'status'             => 'pending',
                    'is_new'             => true,
                ]);

                foreach ($items as $item) {
                    $product = News::findOrFail($item['id']);

                    if (!is_null($product->stock) && $product->stock < $item['qty']) {
                        throw new \Exception('Недостаточно товара на складе: ' . $product->title);
                    }

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item['id'],
                        'title'      => $item['title'],
                        'price'      => $item['price'],
                        'qty'        => $item['qty'],
                    ]);

                    if (!is_null($product->stock)) {
                        $product->decrement('stock', $item['qty']);
                    }
                }

                // Сохраняем ID заказа в сессии для использования после транзакции
                session(['last_order_id' => $order->id]);
            });

            $orderId = session('last_order_id');
            session()->forget(['cart', 'last_order_id']);

            return redirect()->route('cart.confirm', ['id' => $orderId]);
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', 'Ошибка при создании заказа: ' . $e->getMessage());
        }
    }

    public function confirm($id)
    {
        $order = Order::with(['paymentMethod', 'deliveryMethod', 'items'])->findOrFail($id);

        return view('Payments::public.confirm', [
            'paymentMethod'  => $order->paymentMethod,
            'deliveryMethod' => $order->deliveryMethod,
            'order'          => $order,
        ]);
    }
}
