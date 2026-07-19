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

                            <div class="flex-shrink-0">
                                <button formaction="{{ route('cart.remove') }}" formmethod="POST" name="id" value="{{ $item['id'] }}" class="text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                                    <i class="fas fa-trash-alt"></i> Удалить
                                </button>
                            </div>

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
                        <select name="payment_method_id" id="payment-method" required class="w-full border-gray-300 rounded px-4 py-2 shadow-sm focus:ring focus:ring-indigo-300 text-sm">
                            <option value="">Выберите способ оплаты...</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}"
                                        data-description="{{ $method->description ?? '' }}"
                                        data-code="{{ $method->code ?? '' }}"
                                        data-commission="{{ $method->commission ?? 0 }}"
                                        data-min-amount="{{ $method->min_amount ?? 0 }}"
                                        data-max-amount="{{ $method->max_amount ?? 0 }}">
                                    {{ $method->title }}
                                    @if($method->is_russian) (🇷🇺) @endif
                                    @if($method->commission) ({{ $method->formattedCommission }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <p id="payment-description" class="mt-2 text-sm text-gray-600 italic"></p>
                        <p id="payment-commission" class="mt-1 text-sm text-red-600 font-semibold hidden"></p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">🚚 Метод доставки</h2>
                        <select name="delivery_method_id" id="delivery-method" required class="w-full border-gray-300 rounded px-4 py-2 shadow-sm focus:ring focus:ring-indigo-300 text-sm">
                            <option value="">Выберите способ доставки...</option>
                            @foreach ($deliveryMethods as $method)
                                <option value="{{ $method->id }}"
                                        data-price="{{ $method->price }}"
                                        data-description="{{ $method->description ?? '' }}"
                                        data-code="{{ $method->code ?? '' }}"
                                        data-days="{{ $method->delivery_days }}"
                                        data-weight="{{ $method->weight_limit ?? '' }}"
                                        data-regions="{{ $method->regions ? implode(', ', $method->regions) : '' }}">
                                    {{ $method->title }} ({{ number_format($method->price, 2, ',', ' ') }} ₽)
                                    @if($method->is_russian) (🇷🇺) @endif
                                    @if($method->api_enabled) (🌐 API) @endif
                                    @if($method->delivery_days !== '—') [{{ $method->delivery_days }}] @endif
                                </option>
                            @endforeach
                        </select>
                        <p id="delivery-description" class="mt-2 text-sm text-gray-600 italic"></p>
                        <p id="delivery-info" class="mt-1 text-sm text-gray-500 hidden"></p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6 text-center">
                        <div class="text-xl font-bold mb-4">
                            💵 Итого товаров: <span id="cart-total">{{ number_format($total, 2, ',', ' ') }}</span> ₽<br>
                            🚚 Доставка: <span id="delivery-cost">0,00</span> ₽
                            <span id="commission-row" class="hidden"><br>💸 Комиссия: <span id="commission-cost">0,00</span> ₽</span>
                            <hr class="my-2">
                            <span class="text-2xl font-extrabold">💰 Всего к оплате: <span id="grand-total">0,00</span> ₽</span>
                        </div>

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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const deliverySelect = document.getElementById('delivery-method');
        const paymentSelect = document.getElementById('payment-method');
        const deliveryDescription = document.getElementById('delivery-description');
        const deliveryInfo = document.getElementById('delivery-info');
        const paymentDescription = document.getElementById('payment-description');
        const paymentCommission = document.getElementById('payment-commission');
        const deliveryCostSpan = document.getElementById('delivery-cost');
        const commissionCostSpan = document.getElementById('commission-cost');
        const commissionRow = document.getElementById('commission-row');
        const grandTotalSpan = document.getElementById('grand-total');

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

            const delivery = parseFloat(deliveryCostSpan.innerText.replace(/\s/g, '').replace(',', '.')) || 0;
            const commission = parseFloat(commissionCostSpan.innerText.replace(/\s/g, '').replace(',', '.')) || 0;
            const grand = total + delivery + commission;
            grandTotalSpan.innerText = grand.toLocaleString('ru-RU', { minimumFractionDigits: 2 });
        };

        function updateDeliveryInfo() {
            const selected = deliverySelect.options[deliverySelect.selectedIndex];
            if (!selected || !selected.dataset) return;

            const price = parseFloat(selected.dataset.price || 0);
            const desc = selected.dataset.description || '';
            const code = selected.dataset.code || '';
            const days = selected.dataset.days || '';
            const weight = selected.dataset.weight || '';
            const regions = selected.dataset.regions || '';

            deliveryDescription.innerText = desc;
            deliveryCostSpan.innerText = price.toLocaleString('ru-RU', { minimumFractionDigits: 2 });

            // Добавляем информацию о типе доставки
            let infoText = '';
            if (code) infoText += `Код: ${code}`;
            if (days) infoText += `${infoText ? ' • ' : ''}Сроки: ${days}`;
            if (weight) infoText += `${infoText ? ' • ' : ''}Вес до ${weight} кг`;
            if (regions) infoText += `${infoText ? ' • ' : ''}Регионы: ${regions}`;

            if (infoText) {
                deliveryInfo.innerText = infoText;
                deliveryInfo.classList.remove('hidden');
            } else {
                deliveryInfo.classList.add('hidden');
            }

            updateTotals();
        }

        function updatePaymentInfo() {
            const selected = paymentSelect.options[paymentSelect.selectedIndex];
            if (!selected || !selected.dataset) return;

            const desc = selected.dataset.description || '';
            const code = selected.dataset.code || '';
            const commission = parseFloat(selected.dataset.commission || 0);

            paymentDescription.innerText = desc;

            // Отображение комиссии
            if (commission > 0) {
                const cartTotal = parseFloat(document.getElementById('cart-total').innerText.replace(/\s/g, '').replace(',', '.')) || 0;
                const commissionAmount = cartTotal * (commission / 100);

                commissionCostSpan.innerText = commissionAmount.toLocaleString('ru-RU', { minimumFractionDigits: 2 });
                paymentCommission.innerText = `Комиссия: ${commission}% (${commissionAmount.toLocaleString('ru-RU', { minimumFractionDigits: 2 })} ₽)`;
                paymentCommission.classList.remove('hidden');
                commissionRow.classList.remove('hidden');
            } else {
                paymentCommission.classList.add('hidden');
                commissionRow.classList.add('hidden');
                commissionCostSpan.innerText = '0,00';
            }

            // Добавляем информацию о типе оплаты
            if (code) {
                if (paymentDescription.innerText) {
                    paymentDescription.innerText += ` (Код: ${code})`;
                } else {
                    paymentDescription.innerText = `Код: ${code}`;
                }
            }

            updateTotals();
        }

        document.querySelectorAll('.increment').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.querySelector(`.qty-input[data-id="${this.dataset.id}"]`);
                input.value = parseInt(input.value) + 1;
                document.querySelector(`.qty-hidden[data-id="${this.dataset.id}"]`).value = input.value;
                updateTotals();
            });
        });

        document.querySelectorAll('.decrement').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.querySelector(`.qty-input[data-id="${this.dataset.id}"]`);
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                    document.querySelector(`.qty-hidden[data-id="${this.dataset.id}"]`).value = input.value;
                    updateTotals();
                }
            });
        });

        deliverySelect.addEventListener('change', updateDeliveryInfo);
        paymentSelect.addEventListener('change', updatePaymentInfo);

        updateDeliveryInfo();
        updatePaymentInfo();
        updateTotals();
    });
    </script>
@endsection
