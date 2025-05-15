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

        $order = null;

        DB::transaction(function () use ($request, $items, &$order) {
            $total = collect($items)->sum(fn($item) => $item['qty'] * $item['price']);

            $order = Order::create([
                'user_id'            => Auth::check() ? Auth::id() : null,
                'payment_method_id'  => $request->payment_method_id,
                'delivery_method_id' => $request->delivery_method_id,
                'total'              => $total,
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
        });

        session()->forget('cart');

        return redirect()->route('cart.confirm', ['id' => $order->id]);
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
