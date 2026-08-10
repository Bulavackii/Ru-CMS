@extends('layouts.frontend')

@section('title', 'Мои заказы')

@section('content')
@php
    // Подписи статусов — из словаря, тот же набор, что в кабинете.
    // Раньше здесь печаталось сырое значение из базы.
    $statusLabels = [
        'pending' => __('frontend.account.st_pending'),
        'paid' => __('frontend.account.st_paid'),
        'completed' => __('frontend.account.st_completed'),
        'cancelled' => __('frontend.account.st_cancelled'),
        'canceled' => __('frontend.account.st_cancelled'),
    ];
@endphp
    <h1 class="text-2xl font-bold mb-6 text-center">📋 {{ __('frontend.account.my_orders') }}</h1>

    @if ($orders->count())
        <div class="overflow-x-auto">
            <table class="w-full bg-white border border-gray-300 rounded-md shadow text-sm mb-6 min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left whitespace-nowrap">№</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.amount') }}</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.quantity') }}</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.payment') }}</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.delivery') }}</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.status') }}</th>
                        <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('frontend.account.date') }}</th>
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
                                {{-- ⚠️ Здесь выводился ucfirst($order->status): покупатель
                                     на русском языке видел «Completed» и «Pending» —
                                     сырое значение из базы. Подписи берутся из того же
                                     словаря, что и в кабинете.

                                     Классы тоже собирались строкой: bg-{$color}-100.
                                     Tailwind таких имён не видит при сборке, и держалось
                                     это только на том, что нужные классы случайно есть в
                                     полном бандле. Плюс в списке цветов стоял `canceled`
                                     с одной «l», а в базе встречается и `cancelled` —
                                     отменённый заказ красился серым. Теперь цвет
                                     выбирается явно. --}}
                                @php
                                    $tone = match ($order->status) {
                                        'completed', 'paid' => 'ok',
                                        'cancelled', 'canceled' => 'bad',
                                        default => 'wait',
                                    };
                                @endphp
                                <span class="ord-status ord-status--{{ $tone }}">
                                    {{ $statusLabels[$order->status] ?? __('frontend.account.st_unknown') }}
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
        <p class="text-gray-500 text-center">{{ __('frontend.account.orders_empty') }}</p>
    @endif
@endsection

@push('styles')
<style>
    /* Литеральный CSS вместо классов, собранных строкой: Tailwind не видит
       имена вида bg-{$color}-100 при сборке. */
    .ord-status{ display:inline-block; padding:.15rem .5rem; font-size:.72rem; font-weight:700;
                 white-space:nowrap; border:1px solid }
    .ord-status--ok{ color:#15803d; background:#dcfce7; border-color:#86efac }
    .ord-status--bad{ color:#b91c1c; background:#fee2e2; border-color:#fca5a5 }
    .ord-status--wait{ color:#92400e; background:#fef3c7; border-color:#fcd34d }
</style>
@endpush
