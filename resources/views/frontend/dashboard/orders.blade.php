@extends('layouts.frontend')

@section('title', 'Мои заказы')

@section('content')
    <h1 class="text-2xl font-bold mb-6 text-center">📋 Мои заказы</h1>

    @if ($orders->count())
        <div class="overflow-x-auto">
            <table class="w-full bg-white border border-gray-300 rounded-md shadow text-sm mb-6 min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left whitespace-nowrap">№</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Сумма</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Кол-во</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Оплата</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Доставка</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Статус</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-3 py-2 font-semibold whitespace-nowrap">#{{ $order->id }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ number_format($order->total, 2, ',', ' ') }} ₽</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $order->qty ?? $order->items->sum('qty') ?? '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $order->paymentMethod->title ?? '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if ($order->deliveryMethod)
                                    🚚 {{ $order->deliveryMethod->title }}<br>
                                    <span class="text-xs text-gray-500">{{ number_format($order->deliveryMethod->price, 2, ',', ' ') }} ₽</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @php
                                    $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                                    $color = $colors[$order->status] ?? 'gray';
                                @endphp
                                <span class="inline-block px-2 py-1 text-xs rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-center mb-6">
            {{ $orders->appends(request()->query())->links('pagination::tailwind-rus') }}
        </div>
    @else
        <p class="text-gray-500 text-center">У вас пока нет заказов.</p>
    @endif
@endsection
