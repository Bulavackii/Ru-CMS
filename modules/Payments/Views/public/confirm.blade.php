@extends('layouts.frontend')

@section('title', 'Заказ оформлен')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden p-8 space-y-8">
        {{-- ✅ Верхний блок: подтверждение --}}
        <div class="text-center">
            <div class="text-5xl mb-4 text-green-500 animate-bounce">✅</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Ваш заказ успешно оформлен!</h1>
            <p class="text-gray-600 text-sm">Благодарим за покупку — детали заказа указаны ниже</p>
        </div>

        {{-- 🧾 Способ оплаты --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mb-1">
                💳 Способ оплаты:
            </h2>
            <p class="text-base text-gray-700 font-medium">{{ $paymentMethod->title }}</p>
            @if ($paymentMethod->description)
                <p class="text-sm text-gray-500 mt-1">{{ $paymentMethod->description }}</p>
            @endif
        </div>

        {{-- 🚚 Метод доставки --}}
        @isset($deliveryMethod)
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mb-1">
                    🚚 Метод доставки:
                </h2>
                <p class="text-base text-gray-700 font-medium">{{ $deliveryMethod->title }}</p>
                @if ($deliveryMethod->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $deliveryMethod->description }}</p>
                @endif
            </div>
        @endisset

        {{-- 🛒 Список товаров --}}
        @if (isset($order) && $order->items->count())
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    🛍️ Состав заказа
                </h2>
                <div class="space-y-4">
                    @php $total = 0; @endphp
                    @foreach ($order->items as $item)
                        @php
                            $itemTotal = $item->price * $item->qty;
                            $total += $itemTotal;
                        @endphp
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <div class="text-base font-semibold text-gray-800">{{ $item->title }}</div>
                                <div class="text-sm text-gray-600">Цена: {{ number_format($item->price, 2, ',', ' ') }} ₽ ×
                                    {{ $item->qty }}</div>
                            </div>
                            <div class="text-right text-sm font-medium text-gray-800">
                                {{ number_format($itemTotal, 2, ',', ' ') }} ₽
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 text-right text-xl font-bold text-gray-900">
                    💵 Итого к оплате: {{ number_format($total, 2, ',', ' ') }} ₽
                </div>
            </div>
        @endif

        {{-- 🔗 Кнопка возврата --}}
        <div class="text-center mt-10">
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-900 to-black text-white text-sm md:text-base rounded-full font-semibold shadow-lg hover:from-gray-800 hover:to-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600">
                <i class="fas fa-arrow-left"></i>
                На главную
            </a>
        </div>

    </div>
@endsection
