@extends('layouts.admin')

@section('title', 'Заказ #' . $order->id)

@section('content')
    <h1 class="text-2xl font-bold mb-4">📄 Заказ #{{ $order->id }}</h1>

    <p><strong>Пользователь:</strong> {{ $order->user->name ?? 'Гость' }}</p>
    <p><strong>Способ оплаты:</strong> {{ $order->paymentMethod->title ?? '-' }}</p>
    <p><strong>Сумма:</strong> {{ number_format($order->total, 2, ',', ' ') }} ₽</p>
    <p><strong>Статус:</strong> {{ ucfirst($order->status) }}</p>
    <p><strong>Дата:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>

    <hr class="my-4">

    <h2 class="text-xl font-semibold mb-2">🧾 Товары в заказе</h2>

    <table class="w-full bg-white dark:bg-gray-900 border rounded shadow-sm text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
                <th class="px-3 py-2 text-left">Товар</th>
                <th class="px-3 py-2 text-left">Цена</th>
                <th class="px-3 py-2 text-left">Кол-во</th>
                <th class="px-3 py-2 text-left">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <td class="px-3 py-2">{{ $item->title }}</td>
                    <td class="px-3 py-2">{{ number_format($item->price, 2, ',', ' ') }} ₽</td>
                    <td class="px-3 py-2">{{ $item->qty }}</td>
                    <td class="px-3 py-2">{{ number_format($item->price * $item->qty, 2, ',', ' ') }} ₽</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-6">
        <a href="{{ route('admin.orders.index') }}" class="text-blue-600 hover:underline">← Назад к списку</a>
    </div>
@endsection
