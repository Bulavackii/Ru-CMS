<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
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
                'user_id'           => auth()->check() ? auth()->id() : null,
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

    /**
     * 🔄 Обновление статуса заказа
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();
        // Уведомления отправятся автоматически через событие OrderStatusChanged

        return redirect()
            ->back()
            ->with('success', "Статус заказа #{$order->id} обновлён с '{$oldStatus}' на '{$request->status}'");
    }

    /**
     * 💳 Инициализация платежа
     */
    public function initiatePayment(Request $request, Order $order)
    {
        $paymentMethod = PaymentMethod::findOrFail($order->payment_method_id);
        
        try {
            $gatewayService = app(\Modules\Payments\Services\PaymentGatewayService::class);
            $result = $gatewayService->createPayment($order, $paymentMethod);

            if ($result['success']) {
                // Сохраняем payment_id в заказе
                $order->payment_id = $result['payment_id'] ?? null;
                $order->status = 'pending';
                $order->save();

                // Редирект на страницу оплаты
                if (isset($result['confirmation_url'])) {
                    return redirect($result['confirmation_url']);
                } elseif (isset($result['qr_code'])) {
                    return redirect()->route('payments.sbp.qr', ['order' => $order->id])
                        ->with('qr_code', $result['qr_code']);
                }

                return redirect()->back()->with('success', 'Платеж инициализирован');
            }

            return redirect()->back()->with('error', 'Ошибка инициализации платежа');
        } catch (\Exception $e) {
            Log::error('Payment initiation error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    /**
     * 💳 Обработка webhook от платежной системы
     */
    public function webhook(Request $request, string $gateway)
    {
        // 📋 Логирование webhook
        Log::info('Payment webhook received', [
            'gateway' => $gateway,
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $gatewayService = app(\Modules\Payments\Services\PaymentGatewayService::class);
            $handled = $gatewayService->handleWebhook($gateway, $request->all());

            if ($handled) {
                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'ignored'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }



    /**
     * 📊 Получение статистики заказов
     */
    public function stats()
    {
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $revenue = Order::where('status', 'completed')->sum('total');

        return response()->json([
            'total' => $totalOrders,
            'pending' => $pendingOrders,
            'completed' => $completedOrders,
            'revenue' => $revenue,
        ]);
    }

    /**
     * 📋 Экспорт заказов в CSV
     */
    public function export(Request $request)
    {
        $orders = Order::with(['paymentMethod', 'deliveryMethod', 'items', 'user'])
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->date_from, function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->date_to);
            })
            ->get();

        $filename = 'orders_export_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $output = fopen('php://output', 'w');

            // Заголовки
            fputcsv($output, ['ID', 'Дата', 'Клиент', 'Сумма', 'Статус', 'Оплата', 'Доставка']);

            // Данные
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->id,
                    $order->created_at->format('d.m.Y H:i'),
                    $order->user ? $order->user->name : 'Гость',
                    number_format($order->total, 2, '.', ' '),
                    $this->getStatusText($order->status),
                    $order->paymentMethod ? $order->paymentMethod->title : '-',
                    $order->deliveryMethod ? $order->deliveryMethod->title : '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 🗑️ Удаление заказа
     */
    public function destroy(Order $order)
    {
        // 🔄 Восстанавливаем остатки товаров
        foreach ($order->items as $item) {
            $product = News::find($item->product_id);
            if ($product && !is_null($product->stock)) {
                $product->increment('stock', $item->qty);
            }
        }

        $order->delete();

        return redirect()
            ->back()
            ->with('success', "Заказ #{$order->id} удалён, остатки восстановлены");
    }

    /**
     * 📋 Получение текстового представления статуса
     */
    private function getStatusText($status)
    {
        $statuses = [
            'pending' => 'В ожидании',
            'processing' => 'В обработке',
            'completed' => 'Завершён',
            'cancelled' => 'Отменён',
        ];

        return $statuses[$status] ?? $status;
    }
}
