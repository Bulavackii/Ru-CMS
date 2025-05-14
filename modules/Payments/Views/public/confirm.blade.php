@extends('layouts.frontend')

@section('title', 'Заказ оформлен')

@section('content')
    <div class="max-w-2xl mx-auto text-center bg-white shadow rounded p-8">
        <h1 class="text-3xl font-bold text-green-600 mb-4">✅ Заказ оформлен</h1>

        {{-- 🧾 Оплата --}}
        <p class="text-lg mb-2">Вы выбрали способ оплаты:</p>
        <p class="text-xl font-semibold mb-4 text-gray-800">💳 {{ $paymentMethod->title }}</p>

        @if ($paymentMethod->description)
            <div class="text-sm text-gray-600 mb-6">{{ $paymentMethod->description }}</div>
        @endif

        {{-- 🚚 Доставка --}}
        @isset($deliveryMethod)
            <p class="text-lg mb-2">Выбранный способ доставки:</p>
            <p class="text-xl font-semibold mb-4 text-gray-800">🚚 {{ $deliveryMethod->title }}</p>

            @if ($deliveryMethod->description)
                <div class="text-sm text-gray-600 mb-6">{{ $deliveryMethod->description }}</div>
            @endif
        @endisset

        <a href="{{ url('/') }}"
           class="inline-block px-6 py-2 bg-black text-white rounded hover:bg-gray-800 transition">
            ⬅️ На главную
        </a>
    </div>
@endsection
