@extends('layouts.admin')

@section('title', 'Заказ #' . $order->id)

@section('content')
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📄 Заказ #{{ $order->id }}</h1>

    {{-- 🧍 Покупатель: без этого блока заказ некуда отправлять --}}
    <div class="admin-card p-5 mb-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
            <i class="fas fa-user text-indigo-500"></i> {{ __('admin.orders.customer') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <p><span class="text-gray-500">{{ __('admin.orders.c_name') }}:</span>
               <b class="text-gray-900 dark:text-white">{{ $order->customer_name ?: __('admin.orders.c_none') }}</b></p>

            <p><span class="text-gray-500">{{ __('admin.orders.c_phone') }}:</span>
               @if($order->customer_phone)
                   <a href="tel:{{ $order->customer_phone }}" class="text-indigo-600 dark:text-indigo-400">{{ $order->customer_phone }}</a>
               @else
                   <b>{{ __('admin.orders.c_none') }}</b>
               @endif
            </p>

            <p><span class="text-gray-500">{{ __('admin.orders.c_email') }}:</span>
               @if($order->customer_email)
                   <a href="mailto:{{ $order->customer_email }}" class="text-indigo-600 dark:text-indigo-400">{{ $order->customer_email }}</a>
               @else
                   <b>{{ __('admin.orders.c_none') }}</b>
               @endif
            </p>

            <p><span class="text-gray-500">{{ __('admin.orders.c_address') }}:</span>
               <b class="text-gray-900 dark:text-white">{{ $order->customer_address ?: __('admin.orders.c_none') }}</b></p>

            @if($order->comment)
                <p class="md:col-span-2"><span class="text-gray-500">{{ __('admin.orders.c_comment') }}:</span>
                   <span class="text-gray-800 dark:text-gray-200">{{ $order->comment }}</span></p>
            @endif
        </div>
    </div>

    {{-- 🧍 Информация о заказе --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-5 space-y-2">
            <p class="text-sm text-gray-500">👤 Пользователь:</p>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                {{ $order->user->name ?? '🕵️ Гость' }}
            </p>

            <p class="text-sm text-gray-500 mt-4">💳 Способ оплаты:</p>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                {{ $order->paymentMethod->title ?? '—' }}
            </p>

            <p class="text-sm text-gray-500 mt-4">💰 Сумма заказа:</p>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                {{ number_format($order->total, 2, ',', ' ') }} ₽
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-5 space-y-2">
            <p class="text-sm text-gray-500">📌 Статус:</p>
            @php
                $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                $color = $colors[$order->status] ?? 'gray';
            @endphp
            <span
                class="inline-block px-3 py-1 rounded-full bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-800 dark:text-white text-sm font-semibold capitalize">
                {{ $order->status }}
            </span>

            <p class="text-sm text-gray-500 mt-4">🕒 Дата оформления:</p>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                {{ $order->created_at->format('d.m.Y H:i') }}
            </p>

            @if ($order->deliveryMethod)
                <p class="text-sm text-gray-500 mt-4">🚚 Метод доставки:</p>
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ $order->deliveryMethod->title }}
                    @if ($order->deliveryMethod->price)
                        <span
                            class="text-sm text-gray-500 ml-1">({{ number_format($order->deliveryMethod->price, 2, ',', ' ') }}
                            ₽)</span>
                    @endif
                </p>
            @endif
        </div>
    </div>

    {{-- 📦 Таблица товаров --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-5">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">🧾 Товары в заказе</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm table-auto">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left">Товар</th>
                        <th class="px-4 py-2 text-left">Цена</th>
                        <th class="px-4 py-2 text-left">Кол-во</th>
                        <th class="px-4 py-2 text-left">Сумма</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $item->title }}</td>
                            <td class="px-4 py-3">{{ number_format($item->price, 2, ',', ' ') }} ₽</td>
                            <td class="px-4 py-3">{{ $item->qty }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-white">
                                {{ number_format($item->price * $item->qty, 2, ',', ' ') }} ₽
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right text-xl font-bold mt-6 text-gray-900 dark:text-white">
            💵 Итого: {{ number_format($order->total, 2, ',', ' ') }} ₽
        </div>
    </div>

    {{-- 🔙 Назад --}}
    <div class="mt-8">
        {{-- 🔙 Кнопка "Назад к списку" справа --}}
<div class="mt-8 flex justify-end">
    <a href="{{ route('admin.orders.index') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-black hover:bg-gray-800 text-white text-sm font-bold rounded-full shadow-sm transition-all duration-200">
        <i class="fas fa-arrow-left text-xs"></i> Назад
    </a>
</div>
    </div>
@endsection
