@extends('layouts.admin')

@section('title', 'Методы доставки')

@section('content')
    {{-- 🔘 Заголовок и кнопка добавления --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🚚 Методы доставки
        </h1>
        <a href="{{ route('admin.delivery.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Добавить
        </a>
    </div>

    {{-- 📋 Таблица методов доставки --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 border rounded shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🔢</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">📦 Название</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🚚 Тип</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">💰 Цена</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">📅 Сроки</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🎁 Бесплатно от</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">🇷🇺 РФ</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">🌐 API</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">✅ Активен</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($methods as $method)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        {{-- 🔢 Порядок сортировки --}}
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-center">
                            {{ $method->sort_order ?? 0 }}
                        </td>

                        {{-- 📦 Название метода --}}
                        <td class="px-6 py-4 text-gray-800 dark:text-gray-100">
                            {{ $method->title }}
                        </td>

                        {{-- 🚚 Тип метода --}}
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            @switch($method->type)
                                @case('courier')
                                    🚚 Курьер
                                    @break
                                @case('pickup')
                                    🛍️ Самовывоз
                                    @break
                                @case('post')
                                    📦 Почта
                                    @break
                                @case('terminal')
                                    🏧 Терминал
                                    @break
                                @default
                                    {{ $method->type }}
                            @endswitch
                        </td>

                        {{-- 💰 Цена в ₽ --}}
                        <td class="px-6 py-4 text-gray-800 dark:text-white font-semibold">
                            {{ number_format($method->price, 2, ',', ' ') }} ₽
                        </td>

                        {{-- 📅 Сроки доставки --}}
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            {{ $method->delivery_days }}
                        </td>

                        {{-- 🎁 Бесплатная доставка от --}}
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            @if($method->free_delivery_threshold)
                                <span class="text-green-600 dark:text-green-400 font-semibold">
                                    от {{ number_format($method->free_delivery_threshold, 0, ',', ' ') }} ₽
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- 🇷🇺 Российская служба --}}
                        <td class="px-6 py-4 text-center">
                            @if ($method->is_russian)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-white text-xs font-semibold">
                                    🇷🇺
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- 🌐 API интеграция --}}
                        <td class="px-6 py-4 text-center">
                            @if ($method->api_enabled)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-white text-xs font-semibold">
                                    🌐
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- ✅ Статус активности --}}
                        <td class="px-6 py-4 text-center text-xl">
                            {!! $method->active ? '✅' : '❌' !!}
                        </td>

                        {{-- ⚙️ Кнопки действий --}}
                        <td class="px-6 py-4 text-center space-x-2">
                            <a href="{{ route('admin.delivery.edit', $method) }}"
                               class="text-blue-600 hover:text-blue-800 transition" title="Редактировать">
                                ✏️
                            </a>

                            <form action="{{ route('admin.delivery.destroy', $method) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm('Удалить метод доставки?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition" title="Удалить">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Методов доставки пока нет.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
