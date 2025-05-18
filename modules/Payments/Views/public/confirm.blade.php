@extends('layouts.frontend')

@section('title', 'Заказ оформлен')

@section('content')
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 shadow-xl rounded-2xl overflow-hidden p-8 space-y-8 transition">

    {{-- ✅ Заголовок --}}
    <div class="text-center">
        <div class="text-5xl mb-4 text-green-500 animate-bounce">✅</div>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Ваш заказ успешно оформлен!</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Благодарим за покупку — подробности указаны ниже 👇</p>
    </div>

    {{-- 💳 Способ оплаты --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2 mb-1">
            💳 Способ оплаты:
        </h2>
        <p class="text-base text-gray-700 dark:text-gray-300 font-medium">{{ $paymentMethod->title }}</p>
        @if ($paymentMethod->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $paymentMethod->description }}</p>
        @endif
    </div>

    {{-- 🚚 Метод доставки --}}
    @isset($deliveryMethod)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2 mb-1">
                🚚 Метод доставки:
            </h2>
            <p class="text-base text-gray-700 dark:text-gray-300 font-medium">
                {{ $deliveryMethod->title }} — {{ number_format($deliveryMethod->price, 2, ',', ' ') }} ₽
            </p>
            @if ($deliveryMethod->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $deliveryMethod->description }}</p>
            @endif
        </div>
    @endisset

    {{-- 🛍️ Состав заказа --}}
    @if (isset($order) && $order->items->count())
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                🛍️ Состав заказа
            </h2>
            <div class="space-y-4">
                @php $total = 0; @endphp
                @foreach ($order->items as $item)
                    @php
                        $itemTotal = $item->price * $item->qty;
                        $total += $itemTotal;
                    @endphp
                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                        <div>
                            <div class="text-base font-semibold text-gray-800 dark:text-white">{{ $item->title }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Цена: {{ number_format($item->price, 2, ',', ' ') }} ₽ × {{ $item->qty }}
                            </div>
                        </div>
                        <div class="text-right text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ number_format($itemTotal, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                @endforeach

                {{-- 🚚 Строка доставки в составе заказа --}}
                @isset($deliveryMethod)
                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                        <div class="text-base text-gray-600 dark:text-gray-400">Доставка</div>
                        <div class="text-right text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ number_format($deliveryMethod->price, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                    @php $total += $deliveryMethod->price; @endphp
                @endisset
            </div>

            {{-- 💵 Итоговая сумма --}}
            <div class="mt-4 text-right text-xl font-bold text-gray-900 dark:text-white">
                💵 Итого к оплате: {{ number_format($total, 2, ',', ' ') }} ₽
            </div>
        </div>
    @endif

    {{-- 🔗 Кнопка возврата --}}
    <div class="text-center mt-10">
        <a href="{{ url('/') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-900 to-black text-white text-sm md:text-base rounded-full font-semibold shadow-lg hover:from-gray-800 hover:to-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600">
            <i class="fas fa-arrow-left"></i> На главную
        </a>
    </div>
</div>
@endsection
