@extends('layouts.frontend')

@section('title', 'Корзина')

@section('content')
    <h1 class="text-3xl font-bold mb-6 text-center">🛒 Корзина</h1>

    @php
        $total = 0;
    @endphp

    @if (count($cart))
        <form action="{{ route('cart.checkout') }}" method="POST" class="space-y-6">
            @csrf

            <div class="space-y-4">
                @foreach ($cart as $item)
                    @php
                        $subtotal = $item['qty'] * $item['price'];
                        $total += $subtotal;
                    @endphp

                    <div class="border border-gray-300 rounded p-4 bg-white shadow-sm flex justify-between items-center">
                        <div>
                            <div class="text-lg font-semibold">{{ $item['title'] }}</div>
                            <div class="text-sm text-gray-600">Цена: {{ number_format($item['price'], 2, ',', ' ') }} ₽</div>
                            <div class="text-sm text-gray-600">Кол-во: {{ $item['qty'] }}</div>
                            <div class="text-sm text-gray-800 font-bold">Сумма: {{ number_format($subtotal, 2, ',', ' ') }} ₽
                            </div>
                        </div>
                        <button formaction="{{ route('cart.remove') }}" formmethod="POST" name="id"
                            value="{{ $item['id'] }}" class="text-red-600 hover:underline text-sm">
                            ❌ Удалить
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="text-xl font-bold mt-4">
                💵 Итого: {{ number_format($total, 2, ',', ' ') }} ₽
            </div>

            {{-- 💳 Методы оплаты --}}
            <div class="mb-6">
                <label for="payment_method_id" class="block font-semibold mb-2">💳 Способ оплаты</label>
                <select name="payment_method_id" id="payment_method_id" required
                    class="w-full border border-gray-300 rounded px-4 py-2">
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 🚚 Методы доставки --}}
            <div class="mb-6">
                <label for="delivery_method_id" class="block font-semibold mb-2">🚚 Метод доставки</label>
                <select name="delivery_method_id" id="delivery_method_id" required
                    class="w-full border border-gray-300 rounded px-4 py-2">
                    @foreach ($deliveryMethods as $method)
                        <option value="{{ $method->id }}"
                            {{ old('delivery_method_id') == $method->id ? 'selected' : '' }}>
                            {{ $method->name }} ({{ number_format($method->price, 2, ',', ' ') }} ₽)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit"
                    class="bg-black hover:bg-gray-800 text-white px-6 py-3 rounded shadow text-sm font-semibold">
                    ✅ Оформить заказ
                </button>
            </div>
        </form>
    @else
        <p class="text-center text-gray-500">Корзина пуста.</p>
    @endif
@endsection
