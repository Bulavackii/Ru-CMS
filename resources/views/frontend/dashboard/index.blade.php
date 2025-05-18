@extends('layouts.frontend')

@section('title', 'Личный кабинет')

@section('content')
    <h1 class="text-3xl font-extrabold text-center text-blue-900 mb-8">
        👤 Личный кабинет
    </h1>

    {{-- ✅ Сообщение об успехе --}}
    @if (session('success'))
        <div
            class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow text-center animate-fade-in">
             {{ session('success') }}
        </div>
    @endif

    <div
        class="bg-white border border-black rounded-xl shadow-lg mx-auto overflow-visible
               max-w-[95vw] sm:max-w-[720px] md:max-w-[900px] lg:max-w-[1100px] xl:max-w-[1200px]
               sm:px-8 sm:py-8 px-4 py-6">

        {{-- 🧾 Основная информация пользователя --}}
        <div class="space-y-4 text-gray-700 text-sm mb-8">
            <div class="flex items-center gap-3">
                <i class="fas fa-user text-blue-600 text-lg"></i>
                <span><strong>Имя:</strong> {{ $user->name }}</span>
            </div>

            <div class="flex items-center gap-3">
                <i class="fas fa-envelope text-blue-600 text-lg"></i>
                <span><strong>Email:</strong> {{ $user->email }}</span>
            </div>

            <div class="flex items-center gap-3">
                <i class="fas fa-id-badge text-blue-600 text-lg"></i>
                <span><strong>Тип пользователя:</strong> {{ $user->is_company ? 'Юридическое лицо' : 'Физическое лицо' }}</span>
            </div>
        </div>

        {{-- 🏢 Блок для юридического лица --}}
        @if ($user->is_company)
            <div
                class="mb-8 bg-blue-50 border-t border-gray-200 px-6 py-4 rounded-b-lg space-y-4 text-gray-700 text-sm">
                <div class="flex items-center gap-3">
                    <i class="fas fa-building text-indigo-600 text-lg"></i>
                    <span><strong>Компания:</strong> {{ $user->company_name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-invoice text-indigo-600 text-lg"></i>
                    <span><strong>ИНН:</strong> {{ $user->inn }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-clipboard-check text-indigo-600 text-lg"></i>
                    <span><strong>ОГРН:</strong> {{ $user->ogrn }}</span>
                </div>
            </div>
        @endif

        {{-- 📦 Последние заказы --}}
        @if ($orders->count())
            <div class="mb-8 w-full">
                <h2 class="text-lg font-bold text-blue-900 mb-4 text-center">🛍️ Последние заказы</h2>

                {{-- Адаптивная таблица с горизонтальной прокруткой --}}
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] bg-white border border-gray-300 rounded-md shadow text-sm w-full">
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
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        {{ number_format($order->total, 2, ',', ' ') }} ₽
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        {{ $order->qty ?? $order->items->sum('qty') ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        {{ $order->paymentMethod->title ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($order->deliveryMethod)
                                            {{ $order->deliveryMethod->title }}<br>
                                            <span class="text-xs text-gray-500">
                                                {{ number_format($order->deliveryMethod->price, 2, ',', ' ') }} ₽
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @php
                                            $colors = ['pending' => 'gray', 'paid' => 'green', 'canceled' => 'red'];
                                            $color = $colors[$order->status] ?? 'gray';
                                        @endphp
                                        <span
                                            class="inline-block px-2 py-1 text-xs rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        {{ $order->created_at->format('d.m.Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Нижний блок с кнопками слева и ссылкой на все заказы справа --}}
                <div class="flex justify-between items-center mt-4">
                    <div class="flex gap-4">
                        <a href="{{ route('dashboard.edit') }}"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 flex items-center justify-center gap-2">
                            <i class="fas fa-pen"></i> Редактировать
                        </a>

                        @if ($user->is_company)
                            <a href="{{ route('organization.edit') }}"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 flex items-center justify-center gap-2">
                                <i class="fas fa-building"></i> 🏢 Редактировать
                            </a>
                        @endif

                        <a href="{{ route('password.change.form') }}"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 flex items-center justify-center gap-2">
                            <i class="fas fa-lock"></i> Сменить пароль
                        </a>
                    </div>

                    <a href="{{ route('dashboard.orders') }}"
                        class="text-sm text-blue-600 hover:underline transition">→ Все заказы</a>
                </div>
            </div>
        @else
            {{-- Сообщение если заказов нет --}}
            <div class="mt-6 text-sm text-gray-500 text-center px-6 pb-6 select-none">
                🕒 У вас пока нет заказов.
            </div>
        @endif
    </div>
@endsection
