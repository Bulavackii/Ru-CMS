@extends('layouts.frontend')

@section('title', 'Корзина')

@section('content')
    <h1 class="text-3xl font-bold mb-6 text-center">🛒 Корзина</h1>

    @php $total = 0; @endphp

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
                            <div class="text-sm text-gray-600">Цена: <span class="price">{{ number_format($item['price'], 2, ',', ' ') }}</span> ₽</div>

                            <div class="text-sm text-gray-600 flex items-center gap-2 mt-1">
                                <span>Кол-во:</span>
                                <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                                    <button type="button" class="px-2 bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold text-lg decrement" data-id="{{ $item['id'] }}">−</button>
                                    <input type="text" readonly value="{{ $item['qty'] }}" class="w-10 text-center border-l border-r border-gray-200 text-sm qty-input" data-id="{{ $item['id'] }}">
                                    <button type="button" class="px-2 bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold text-lg increment" data-id="{{ $item['id'] }}">+</button>
                                </div>
                            </div>

                            <div class="text-sm text-gray-800 font-bold mt-1">
                                Сумма: <span class="subtotal">{{ number_format($subtotal, 2, ',', ' ') }}</span> ₽
                            </div>
                        </div>
                        <button formaction="{{ route('cart.remove') }}" formmethod="POST" name="id" value="{{ $item['id'] }}" class="text-red-600 hover:underline text-sm">
                            ❌ Удалить
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="text-xl font-bold mt-4">💵 Итого: <span id="cart-total">{{ number_format($total, 2, ',', ' ') }}</span> ₽</div>

            <div class="mb-6">
                <label for="payment_method_id" class="block font-semibold mb-2">💳 Способ оплаты</label>
                <select name="payment_method_id" id="payment_method_id" required class="w-full border border-gray-300 rounded px-4 py-2">
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label for="delivery_method_id" class="block font-semibold mb-2">🚚 Метод доставки</label>
                <select name="delivery_method_id" id="delivery_method_id" required class="w-full border border-gray-300 rounded px-4 py-2">
                    @foreach ($deliveryMethods as $method)
                        <option value="{{ $method->id }}" {{ old('delivery_method_id') == $method->id ? 'selected' : '' }}>
                            {{ $method->name }} ({{ number_format($method->price, 2, ',', ' ') }} ₽)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="bg-black hover:bg-gray-800 text-white px-6 py-3 rounded shadow text-sm font-semibold">
                    ✅ Оформить заказ
                </button>
            </div>
        </form>
    @else
        <p class="text-center text-gray-500">Корзина пуста.</p>
    @endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.increment').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const input = document.querySelector(`.qty-input[data-id='${id}']`);
            let qty = parseInt(input.value);
            input.value = ++qty;
            updateCart(id, qty);
        });
    });

    document.querySelectorAll('.decrement').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const input = document.querySelector(`.qty-input[data-id='${id}']`);
            let qty = parseInt(input.value);
            if (qty > 1) {
                input.value = --qty;
                updateCart(id, qty);
            } else {
                // отправка на удаление
                fetch("{{ route('cart.remove') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id })
                }).then(() => location.reload());
            }
        });
    });

    function updateCart(id, qty) {
        fetch("{{ route('cart.update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id, qty })
        }).then(res => res.json()).then(data => {
            document.querySelector(`.qty-input[data-id='${id}']`).value = data.qty;
            document.querySelector(`.qty-input[data-id='${id}']`).closest('.border').querySelector('.subtotal').innerText = data.subtotal;
            document.getElementById('cart-total').innerText = data.total;
        });
    }
</script>
@endpush
