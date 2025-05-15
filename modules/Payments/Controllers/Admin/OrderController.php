<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Payments\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        Order::where('is_new', true)->update(['is_new' => false]);

        $orders = Order::with(['paymentMethod', 'deliveryMethod', 'items'])
            ->latest()
            ->paginate(15);

        return view('Payments::admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'paymentMethod', 'deliveryMethod', 'user']);
        return view('Payments::admin.orders.show', compact('order'));
    }
}
