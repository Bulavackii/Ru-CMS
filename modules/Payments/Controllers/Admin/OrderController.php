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
    public function index()
    {
        Order::where('is_new', true)->update(['is_new' => false]);

        $orders = Order::with(['paymentMethod', 'deliveryMethod', 'items', 'user'])
            ->latest()
            ->paginate(15);

        return view('Payments::admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'paymentMethod', 'deliveryMethod', 'user']);
        return view('Payments::admin.orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:news,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'delivery_method_id' => 'nullable|exists:delivery_methods,id',
        ]);

        DB::transaction(function () use ($request) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'new',
                'is_new' => true,
                'payment_method_id' => $request->payment_method_id,
                'delivery_method_id' => $request->delivery_method_id,
            ]);

            foreach ($request->items as $item) {
                $product = News::findOrFail($item['id']);

                if (!is_null($product->stock) && $product->stock < $item['qty']) {
                    throw new \Exception('Недостаточно товара на складе: ' . $product->title);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                ]);

                if (!is_null($product->stock)) {
                    $product->decrement('stock', $item['qty']);
                }
            }
        });

        return redirect()->route('dashboard.orders')->with('success', 'Заказ оформлен и остаток товаров обновлён.');
    }
}
