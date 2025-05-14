<?php

namespace Modules\Payments\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Delivery\Models\DeliveryMethod;

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
        $cart = session('cart', []);
        $id = $request->input('id');

        $cart[$id] = [
            'id'    => $id,
            'title' => $request->input('title'),
            'price' => floatval($request->input('price')),
            'qty'   => intval($request->input('qty')),
        ];

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

    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'delivery_method_id' => 'required|exists:delivery_methods,id',
        ]);

        $cart = session('cart', []);
        $total = collect($cart)->sum(fn($item) => $item['qty'] * $item['price']);

        // 💾 Создание заказа
        $order = Order::create([
            'user_id'            => Auth::check() ? Auth::id() : null,
            'payment_method_id'  => $request->payment_method_id,
            'delivery_method_id' => $request->delivery_method_id,
            'total'              => $total,
            'status'             => 'pending',
        ]);

        // 💾 Создание позиций заказа
        foreach ($cart as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['id'],
                'title'      => $item['title'],
                'price'      => $item['price'],
                'qty'        => $item['qty'],
            ]);
        }

        session()->forget('cart');

        return view('Payments::public.confirm', [
            'paymentMethod'  => $order->paymentMethod,
            'deliveryMethod' => $order->deliveryMethod,
        ]);
    }
}
