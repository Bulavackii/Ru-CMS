@extends('layouts.frontend')

@section('title', 'Корзина')

@section('content')
    <h1 class="text-3xl font-bold mb-8 text-center">🛒 {{ __('frontend.cart.title') }}</h1>

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
                                    <span class="text-sm text-gray-600">{{ __('frontend.cart.quantity') }}</span>
                                    <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                                        <button type="button" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-lg decrement" data-id="{{ $item['id'] }}">−</button>
                                        <input type="text" readonly value="{{ $item['qty'] }}" class="w-12 text-center border-x border-gray-200 text-sm qty-input" data-id="{{ $item['id'] }}">
                                        <button type="button" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-lg increment" data-id="{{ $item['id'] }}">+</button>
                                    </div>
                                </div>

                                <div class="mt-2 font-bold text-sm text-gray-800">{{ __('frontend.cart.sum') }} <span class="subtotal">{{ number_format($subtotal, 2, ',', ' ') }}</span> ₽</div>
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
                        <h2 class="text-lg font-semibold mb-4">💳 {{ __('frontend.cart.payment_method') }}</h2>
                        {{-- Карточки вместо выпадающего списка: описание и
                             комиссия видны сразу у каждого способа, а не
                             после выбора. --}}
                        @forelse ($paymentMethods as $method)
                            <label class="pay-option">
                                <input type="radio" name="payment_method_id" value="{{ $method->id }}" required
                                       class="pay-option__radio"
                                       data-description="{{ $method->description ?? '' }}"
                                       data-code="{{ $method->code ?? '' }}"
                                       data-commission="{{ $method->commission ?? 0 }}"
                                       data-min-amount="{{ $method->min_amount ?? 0 }}"
                                       data-max-amount="{{ $method->max_amount ?? 0 }}">
                                <span class="pay-option__body">
                                    <span class="pay-option__head">
                                        <b>{{ $method->title }}</b>
                                        @if($method->commission > 0)
                                            <span class="pay-option__fee">{{ $method->formattedCommission }}</span>
                                        @endif
                                    </span>
                                    @if($method->description)
                                        <span class="pay-option__note">{{ $method->description }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('frontend.cart.no_payment') }}</p>
                        @endforelse

                        <p id="payment-description" class="mt-2 text-sm text-gray-600 italic"></p>
                        <p id="payment-commission" class="mt-1 text-sm text-red-600 font-semibold hidden"></p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">🚚 {{ __('frontend.cart.delivery_method') }}</h2>
                        {{-- Карточки вместо выпадающего списка: стоимость и срок видны
                             сразу у каждой службы, а не после выбора. --}}
                        @forelse ($deliveryMethods as $method)
                            <label class="pay-option">
                                <input type="radio" name="delivery_method_id" value="{{ $method->id }}" required
                                       class="pay-option__radio"
                                       data-price="{{ $method->price }}"
                                       data-description="{{ $method->description ?? '' }}"
                                       data-code="{{ $method->code ?? '' }}"
                                       data-days="{{ $method->delivery_days }}"
                                       data-weight="{{ $method->weight_limit ?? '' }}">
                                <span class="pay-option__body">
                                    <span class="pay-option__head">
                                        <b>{{ $method->title }}</b>
                                        <span class="pay-option__fee">{{ $method->formatted_price }}</span>
                                    </span>
                                    <span class="pay-option__note">
                                        @if($method->description){{ $method->description }} · @endif
                                        {{ $method->delivery_days }}
                                        @if($method->free_delivery_threshold > 0)
                                            · {{ __('frontend.cart.free_delivery_from', ['sum' => number_format((float) $method->free_delivery_threshold, 0, ',', ' ')]) }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('frontend.cart.no_delivery') }}</p>
                        @endforelse
                        <p id="delivery-description" class="mt-2 text-sm text-gray-600 italic"></p>
                        <p id="delivery-info" class="mt-1 text-sm text-gray-500 hidden"></p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow p-6 text-center">
                        <div class="text-xl font-bold mb-4">
                            💵 Итого товаров: <span id="cart-total">{{ number_format($total, 2, ',', ' ') }}</span> ₽<br>
                            🚚 Доставка: <span id="delivery-cost">0,00</span> ₽
                            <span id="commission-row" class="hidden"><br>💸 {{ __('frontend.cart.fee') }} <span id="commission-cost">0,00</span> ₽</span>
                            <hr class="my-2">
                            <span class="text-2xl font-extrabold">💰 {{ __('frontend.cart.total') }} <span id="grand-total">0,00</span> ₽</span>
                        </div>

                        <button type="submit" class="bg-black hover:bg-gray-800 text-white px-8 py-3 rounded-md shadow-md font-semibold transition">
                            Оформить заказ
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
        <p class="text-center text-gray-500 text-lg">{{ __('frontend.cart.empty') }}</p>
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

@push('styles')
<style>
    /* Способы оплаты карточками. Литеральный CSS: в статической сборке
       Tailwind нет ни :has(), ни нужных вариантов. */
    .pay-option { display: block; border: 1px solid #e5e7eb; padding: .75rem 1rem; margin-bottom: .5rem;
                  cursor: pointer; background: #fff; transition: border-color .15s, box-shadow .15s }
    .pay-option:hover { border-color: #a5b4fc }
    .pay-option:has(.pay-option__radio:checked) { border-color: #6366f1; box-shadow: inset 0 0 0 1px #6366f1 }
    .pay-option__radio { margin-right: .6rem; vertical-align: top; margin-top: .25rem }
    .pay-option__body { display: inline-block; width: calc(100% - 1.6rem) }
    .pay-option__head { display: flex; align-items: center; justify-content: space-between; gap: .5rem }
    .pay-option__fee { font-size: 12px; color: #b91c1c; white-space: nowrap }
    .pay-option__note { display: block; font-size: 13px; color: #6b7280; margin-top: .15rem }
</style>
@endpush
