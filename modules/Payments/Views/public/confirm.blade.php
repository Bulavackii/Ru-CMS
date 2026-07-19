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
        <p class="text-base text-gray-700 dark:text-gray-300 font-medium">{{ $paymentMethod->title }}
            @if($paymentMethod->is_russian) <span class="text-blue-600">🇷🇺</span> @endif
            @if($paymentMethod->code) <span class="text-sm text-gray-500">({{ $paymentMethod->code }})</span> @endif
        </p>
        @if ($paymentMethod->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $paymentMethod->description }}</p>
        @endif
        @if ($paymentMethod->commission)
            <p class="text-sm text-red-600 font-semibold mt-1">Комиссия: {{ $paymentMethod->formattedCommission }}</p>
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
                @if($deliveryMethod->is_russian) <span class="text-blue-600">🇷🇺</span> @endif
                @if($deliveryMethod->api_enabled) <span class="text-purple-600">🌐 API</span> @endif
                @if($deliveryMethod->code) <span class="text-sm text-gray-500">({{ $deliveryMethod->code }})</span> @endif
            </p>
            @if ($deliveryMethod->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $deliveryMethod->description }}</p>
            @endif
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                @if ($deliveryMethod->delivery_days !== '—')
                    <span class="inline-block mr-3">📅 Сроки: {{ $deliveryMethod->delivery_days }}</span>
                @endif
                @if ($deliveryMethod->weight_limit)
                    <span class="inline-block mr-3">⚖️ Вес до {{ $deliveryMethod->weight_limit }} кг</span>
                @endif
                @if ($deliveryMethod->regions && count($deliveryMethod->regions) > 0)
                    <span class="inline-block">🗺️ Регионы: {{ implode(', ', $deliveryMethod->regions) }}</span>
                @endif
            </div>
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
                        <div class="text-base text-gray-600 dark:text-gray-400">Доставка ({{ $deliveryMethod->title }})</div>
                        <div class="text-right text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ number_format($deliveryMethod->price, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                    @php $total += $deliveryMethod->price; @endphp
                @endisset

                {{-- 💸 Строка комиссии --}}
                @if (isset($paymentMethod) && $paymentMethod->commission)
                    @php
                        $commissionAmount = $total * ($paymentMethod->commission / 100);
                        $total += $commissionAmount;
                    @endphp
                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                        <div class="text-base text-red-600 font-semibold">Комиссия ({{ $paymentMethod->formattedCommission }})</div>
                        <div class="text-right text-sm font-medium text-red-600">
                            {{ number_format($commissionAmount, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                @endif
            </div>

            {{-- 💵 Итоговая сумма --}}
            <div class="mt-4 text-right text-xl font-bold text-gray-900 dark:text-white">
                💵 Итого к оплате: {{ number_format($total, 2, ',', ' ') }} ₽
            </div>
        </div>
    @endif

    {{-- 📋 Детали заказа --}}
    @if (isset($order))
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                📋 Детали заказа
            </h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Номер заказа:</span>
                    <span class="font-semibold text-gray-800 dark:text-white">#{{ $order->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Дата создания:</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Статус:</span>
                    <span class="font-semibold text-green-600">Новый</span>
                </div>
            </div>
        </div>
    @endif

    {{-- � Кнопка возврата --}}
    <div class="text-center mt-10 space-y-3">
        <a href="{{ url('/') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-900 to-black text-white text-sm md:text-base rounded-full font-semibold shadow-lg hover:from-gray-800 hover:to-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600">
            <i class="fas fa-arrow-left"></i> На главную
        </a>
        @if (auth()->check())
            <div>
                <a href="{{ route('dashboard.orders') }}"
                   class="text-sm text-blue-600 hover:text-blue-800 underline">
                    Посмотреть все заказы в личном кабинете →
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
