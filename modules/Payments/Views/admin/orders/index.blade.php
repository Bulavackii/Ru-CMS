@extends('layouts.admin')

@section('title', 'Заказы')

@section('content')
    <h1 class="text-2xl font-bold mb-6">📦 Список заказов</h1>

    <table class="w-full table-auto bg-white dark:bg-gray-900 rounded shadow overflow-hidden">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
            <tr>
                <th class="px-4 py-3 text-left">№</th>
                <th class="px-4 py-3 text-left">Пользователь</th>
                <th class="px-4 py-3 text-left">Сумма</th>
                <th class="px-4 py-3 text-left">Оплата</th>
                <th class="px-4 py-3 text-left">Статус</th>
                <th class="px-4 py-3 text-left">Дата</th>
                <th class="px-4 py-3 text-left">Действия</th>
            </tr>
        </thead>
        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($orders as $order)
                <tr>
                    <td class="px-4 py-2 font-semibold">#{{ $order->id }}</td>
                    <td class="px-4 py-2">{{ $order->user->name ?? 'Гость' }}</td>
                    <td class="px-4 py-2">{{ number_format($order->total, 2, ',', ' ') }} ₽</td>
                    <td class="px-4 py-2">{{ $order->paymentMethod->title ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @php
                            $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                            $color = $colors[$order->status] ?? 'gray';
                        @endphp
                        <span class="px-2 py-1 rounded-full bg-{{ $color }}-100 text-{{ $color }}-800 text-xs font-medium">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">Подробнее</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
@endsection
