@extends('layouts.frontend')

@section('title', 'Мои заказы')

@section('content')
    <h1 class="text-2xl font-bold mb-6 text-center">📋 Мои заказы</h1>

    @if ($orders->count())
        <table class="w-full bg-white border border-gray-300 rounded-md shadow text-sm mb-6">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">№</th>
                    <th class="px-3 py-2 text-left">Сумма</th>
                    <th class="px-3 py-2 text-left">Оплата</th>
                    <th class="px-3 py-2 text-left">Доставка</th>
                    <th class="px-3 py-2 text-left">Статус</th>
                    <th class="px-3 py-2 text-left">Дата</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr class="border-t border-gray-100">
                        <td class="px-3 py-2 font-semibold">#{{ $order->id }}</td>
                        <td class="px-3 py-2">{{ number_format($order->total, 2, ',', ' ') }} ₽</td>
                        <td class="px-3 py-2">{{ $order->paymentMethod->title ?? '-' }}</td>
                        <td class="px-3 py-2">
                            @if ($order->deliveryMethod)
                                🚚 {{ $order->deliveryMethod->title }}<br>
                                <span class="text-xs text-gray-500">{{ number_format($order->deliveryMethod->price, 2, ',', ' ') }} ₽</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                                $color = $colors[$order->status] ?? 'gray';
                            @endphp
                            <span class="inline-block px-2 py-1 text-xs rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div>
            {{ $orders->links() }}
        </div>
    @else
        <p class="text-gray-500 text-center">У вас пока нет заказов.</p>
    @endif
@endsection
