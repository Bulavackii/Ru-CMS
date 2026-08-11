<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
use Modules\News\Models\News;

class OrderController extends Controller
{
    /**
     * 📦 Список заказов в админке
     */
    public function index(Request $request)
    {
        // Отметку «новый» снимаем ДО выборки, но запоминаем, какие заказы
        // были новыми: иначе бейдж «Новый» исчезал ещё до того, как
        // владелец увидел список.
        $freshIds = Order::where('is_new', true)->pluck('id')->all();
        Order::where('is_new', true)->update(['is_new' => false]);

        $orders = Order::with(['paymentMethod', 'deliveryMethod', 'items', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->input('q'));

                // Поиск по номеру и по покупателю разом: владелец ищет
                // «как получится» — по цифре из письма или по фамилии.
                $query->where(function ($inner) use ($term) {
                    $inner->where('customer_name', 'like', "%{$term}%")
                        ->orWhere('customer_phone', 'like', "%{$term}%")
                        ->orWhere('customer_email', 'like', "%{$term}%");

                    if (ctype_digit($term)) {
                        $inner->orWhere('id', (int) $term);
                    }
                });
            })
            ->latest();

        // Сводку считаем по ВСЕЙ выборке, а не по странице: иначе она
        // отвечала бы на бессмысленный вопрос «сколько на этом экране».
        $summary = [
            'count' => (clone $orders)->count(),
            'amount' => (float) (clone $orders)->sum('total'),
            'pending' => (clone $orders)->whereIn('status', ['pending', 'processing'])->count(),
            'done' => (clone $orders)->where('status', 'completed')->count(),
        ];

        $orders = $orders->paginate(15)->withQueryString();

        return view('Payments::admin.orders.index', compact('orders', 'freshIds', 'summary'));
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
            ->with('success', __('admin.flash.order_placed'));
    }

    /**
     * 🔄 Обновление статуса заказа
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Набор берём у модели: раньше он был переписан здесь руками и
        // разошёлся со списком во вьюхе — «Оплачен» не сохранялся.
        $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();
        // Уведомления отправятся автоматически через событие OrderStatusChanged

        return redirect()
            ->back()
            ->with('success', __('admin.flash.order_status_changed', ['id' => $order->id, 'from' => $oldStatus, 'to' => $request->status]));
    }

    /**
     * 💳 Инициализация платежа
     */
    public function initiatePayment(Request $request, Order $order)
    {
        // Заказ привязывается моделью по идентификатору из адреса, а
        // маршрут закрыт только auth — без этой проверки любой вошедший
        // пользователь мог запустить оплату ЧУЖОГО заказа и заодно
        // сбросить его статус в pending, в том числе у уже оплаченного.
        $user = $request->user();

        if (! $user?->is_admin && $order->user_id !== $user?->id) {
            abort(403);
        }

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

                return redirect()->back()->with('success', __('admin.flash.payment_started'));
            }

            return redirect()->back()->with('error', __('admin.flash.payment_start_failed'));
        } catch (\Exception $e) {
            Log::error('Payment initiation error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('admin.flash.error_generic', ['message' => $e->getMessage()]));
        }
    }

    /**
     * 💳 Обработка webhook от платежной системы
     */
    /**
     * Возврат покупателя с платёжной страницы.
     *
     * Сюда платёжная система приводит человека после оплаты ИЛИ отказа —
     * и сюда же можно просто вернуться кнопкой «назад». Поэтому успех
     * здесь не объявляется: показываем реальный статус заказа, а
     * подтверждает оплату webhook.
     */
    public function paymentReturn(Request $request, $order)
    {
        $order = \Modules\Payments\Models\Order::find($order);

        if (! $order) {
            return redirect()->route('cart.index')->with('error', __('frontend.cart.order_not_found'));
        }

        $flash = match ($order->status) {
            'completed' => ['success', __('frontend.cart.payment_confirmed')],
            'cancelled' => ['error', __('frontend.cart.payment_cancelled')],
            default => ['info', __('frontend.cart.payment_pending')],
        };

        return redirect()
            ->route('cart.confirm', ['id' => $order->id])
            ->with($flash[0], $flash[1]);
    }

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
            ->with('success', __('admin.flash.order_deleted', ['id' => $order->id]));
    }

    /**
     * 📋 Получение текстового представления статуса
     */
    private function getStatusText($status)
    {
        return Order::statusLabels()[$status] ?? $status;
    }
}
