@extends('layouts.frontend')

@section('title', 'Личный кабинет')

@section('content')
    <h1 class="text-3xl font-extrabold text-center text-blue-900 mb-8">
        👤 Личный кабинет
    </h1>

    {{-- ✅ Сообщение об успехе --}}
    @if (session('success'))
        <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow text-center">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-black rounded-xl shadow-lg max-w-2xl mx-auto overflow-hidden">
        {{-- 🧾 Основной блок --}}
        <div class="p-6 space-y-3 text-sm text-gray-700">
            <div class="flex items-center gap-2">
                <i class="fas fa-user text-blue-600"></i>
                <span><strong>Имя:</strong> {{ $user->name }}</span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-envelope text-blue-600"></i>
                <span><strong>Email:</strong> {{ $user->email }}</span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-id-badge text-blue-600"></i>
                <span><strong>Тип пользователя:</strong> {{ $user->is_company ? 'Юридическое лицо' : 'Физическое лицо' }}</span>
            </div>
        </div>

        {{-- 🏢 Блок юр. лица --}}
        @if($user->is_company)
            <div class="bg-blue-50 border-t border-gray-200 px-6 py-4 space-y-3 text-sm text-gray-700">
                <div class="flex items-center gap-2">
                    <i class="fas fa-building text-indigo-600"></i>
                    <span><strong>Компания:</strong> {{ $user->company_name }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice text-indigo-600"></i>
                    <span><strong>ИНН:</strong> {{ $user->inn }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-indigo-600"></i>
                    <span><strong>ОГРН:</strong> {{ $user->ogrn }}</span>
                </div>
            </div>
        @endif

        {{-- 🔧 Действия --}}
        <div class="flex flex-col sm:flex-row justify-center gap-4 p-6 border-t border-gray-200 bg-gray-50">
            <a href="{{ route('dashboard.edit') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 text-center">
                ✏️ Редактировать
            </a>

            @if($user->is_company)
                <a href="{{ route('organization.edit') }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 text-center">
                    🏢 Редактировать
                </a>
            @endif

            <a href="{{ route('password.change.form') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 text-center">
                🔒 Сменить пароль
            </a>
        </div>

        {{-- 📦 Последние заказы --}}
        @if ($orders->count())
            <div class="mt-6 border-t border-gray-200 pt-6 space-y-4 text-sm text-gray-700 px-6 pb-6">
                <h2 class="text-lg font-bold text-blue-900">🛍️ Последние заказы</h2>

                <table class="w-full bg-white border border-gray-300 rounded-md shadow text-sm">
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
                                        {{ $order->deliveryMethod->title }}<br>
                                        <span class="text-xs text-gray-500">{{ number_format($order->deliveryMethod->price, 2, ',', ' ') }} ₽</span>
                                    @else
                                        —
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

                <div class="text-right">
                    <a href="{{ route('dashboard.orders') }}"
                       class="text-sm text-blue-600 hover:underline">
                        → Все заказы
                    </a>
                </div>
            </div>
        @else
            <div class="mt-6 text-sm text-gray-500 text-center px-6 pb-6">
                🕒 У вас пока нет заказов.
            </div>
        @endif
    </div>
@endsection
