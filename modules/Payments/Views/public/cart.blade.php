@extends('layouts.frontend')

@section('title', 'Корзина')

@section('content')
    <h1 class="text-3xl font-bold mb-8 text-center">🛒 Ваша корзина</h1>

    @php $total = 0; @endphp

    @if (count($cart))
        <form action="{{ route('cart.checkout') }}" method="POST" class="max-w-6xl mx-auto px-4">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- 🧺 Список товаров --}}
                <div class="space-y-6 lg:col-span-2">
                    @foreach ($cart as $item)
                        @php
                            $subtotal = $item['qty'] * $item['price'];
                            $total += $subtotal;
                        @endphp

                        <div class="flex flex-col md:flex-row justify-between items-center border border-gray-300 rounded-lg p-5 bg-white shadow-sm gap-4">
                            <div class="flex-1 w-full">
                                <div class="text-lg font-semibold">{{ $item['title'] }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    Цена: <span class="price">{{ number_format($item['price'], 2, ',', ' ') }}</span> ₽
                                </div>

                                <div class="flex items-center gap-2 mt-3">
                                    <span class="text-sm text-gray-600">Кол-во:</span>
                                    <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                                        <button type="button" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-lg decrement" data-id="{{ $item['id'] }}">−</button>
                                        <input type="text" readonly value="{{ $item['qty'] }}" class="w-12 text-center border-x border-gray-200 text-sm qty-input" data-id="{{ $item['id'] }}">
                                        <button type="button" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-lg increment" data-id="{{ $item['id'] }}">+</button>
                                    </div>
                                </div>

                                <div class="mt-2 font-bold text-sm text-gray-800">Сумма: <span class="subtotal">{{ number_format($subtotal, 2, ',', ' ') }}</span> ₽</div>
                            </div>

                            {{-- Удалить --}}
                            <div class="flex-shrink-0">
                                <button formaction="{{ route('cart.remove') }}" formmethod="POST" name="id" value="{{ $item['id'] }}" class="text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                                    <i class="fas fa-trash-alt"></i> Удалить
                                </button>
                            </div>

                            {{-- 🆕 Скрытые поля для передачи данных --}}
                            <input type="hidden" name="items[{{ $item['id'] }}][id]" value="{{ $item['id'] }}">
                            <input type="hidden" name="items[{{ $item['id'] }}][title]" value="{{ $item['title'] }}">
                            <input type="hidden" name="items[{{ $item['id'] }}][price]" value="{{ $item['price'] }}">
                            <input type="hidden" name="items[{{ $item['id'] }}][qty]" class="qty-hidden" data-id="{{ $item['id'] }}" value="{{ $item['qty'] }}">
                        </div>
                    @endforeach
                </div>

                {{-- 🧾 Оформление --}}
                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">💳 Способ оплаты</h2>
                        <select name="payment_method_id" required class="w-full border-gray-300 rounded px-4 py-2 shadow-sm focus:ring focus:ring-indigo-300 text-sm">
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">🚚 Метод доставки</h2>
                        <select name="delivery_method_id" required class="w-full border-gray-300 rounded px-4 py-2 shadow-sm focus:ring focus:ring-indigo-300 text-sm">
                            @foreach ($deliveryMethods as $method)
                                <option value="{{ $method->id }}" {{ old('delivery_method_id') == $method->id ? 'selected' : '' }}>
                                    {{ $method->name }} ({{ number_format($method->price, 2, ',', ' ') }} ₽)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6 text-center">
                        <div class="text-xl font-bold mb-4">💵 Итого: <span id="cart-total">{{ number_format($total, 2, ',', ' ') }}</span> ₽</div>

                        <button type="submit" class="bg-black hover:bg-gray-800 text-white px-8 py-3 rounded-md shadow-md font-semibold transition">
                             Оформить заказ
                        </button>
                    </div>
                </div>

            </div>
        </form>
    @else
        <p class="text-center text-gray-500 text-lg">Ваша корзина пуста.</p>
    @endif

    {{-- ✅ JS-логика --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const updateTotals = () => {
            let total = 0;

            document.querySelectorAll('.qty-input').forEach(input => {
                const parent = input.closest('.flex-1');
                const priceText = parent.querySelector('.price').innerText.replace(/\s/g, '').replace(',', '.');
                const qty = parseInt(input.value);
                const price = parseFloat(priceText);

                const subtotal = qty * price;
                parent.querySelector('.subtotal').innerText = subtotal.toLocaleString('ru-RU', { minimumFractionDigits: 2 });

                total += subtotal;
            });

            document.getElementById('cart-total').innerText = total.toLocaleString('ru-RU', { minimumFractionDigits: 2 });
        };

        document.querySelectorAll('.increment').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.querySelector(`.qty-input[data-id="${this.dataset.id}"]`);
                input.value = parseInt(input.value) + 1;

                const hiddenInput = document.querySelector(`.qty-hidden[data-id="${this.dataset.id}"]`);
                if (hiddenInput) hiddenInput.value = input.value;

                updateTotals();
            });
        });

        document.querySelectorAll('.decrement').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.querySelector(`.qty-input[data-id="${this.dataset.id}"]`);
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;

                    const hiddenInput = document.querySelector(`.qty-hidden[data-id="${this.dataset.id}"]`);
                    if (hiddenInput) hiddenInput.value = input.value;

                    updateTotals();
                }
            });
        });

        updateTotals();
    });
    </script>
@endsection
