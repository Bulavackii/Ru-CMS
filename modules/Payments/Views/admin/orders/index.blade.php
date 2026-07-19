@extends('layouts.admin')

@section('title', 'Заказы')

@section('content')
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
        📦 Список заказов
    </h1>

    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-xl shadow">
        <table class="w-full table-auto text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">👤 Пользователь</th>
                    <th class="px-4 py-3 text-left">💰 Сумма</th>
                    <th class="px-4 py-3 text-left">💳 Оплата</th>
                    <th class="px-4 py-3 text-left">🚚 Доставка</th>
                    <th class="px-4 py-3 text-left">📌 Статус</th>
                    <th class="px-4 py-3 text-left">🕒 Дата</th>
                    <th class="px-4 py-3 text-left">🔍 Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                @foreach ($orders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-semibold">#{{ $order->id }}</td>
                        <td class="px-4 py-3">
                            {{ $order->user->name ?? '🕵️ Гость' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ number_format($order->total, 2, ',', ' ') }} ₽
                        </td>
                        <td class="px-4 py-3">
                            {{ $order->paymentMethod->title ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($order->deliveryMethod)
                                {{ $order->deliveryMethod->title }}<br>
                                <span class="text-xs text-gray-500">
                                    {{ number_format($order->deliveryMethod->price, 2, ',', ' ') }} ₽
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                                $color = $colors[$order->status] ?? 'gray';
                            @endphp
                            <span class="inline-block px-2 py-1 rounded-full bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-800 dark:text-white text-xs font-medium capitalize">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $order->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 text-sm">
                                <i class="fas fa-eye"></i> Подробнее
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $orders->links('vendor.pagination.tailwind') }}
    </div>
@endsection
