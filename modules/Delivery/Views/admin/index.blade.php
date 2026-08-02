@extends('layouts.admin')

@section('title', __('admin.delivery.list'))

@section('content')
    {{-- 🔘 Заголовок и кнопка добавления --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🚚 {{ __('admin.delivery.list') }}
        </h1>
        <a href="{{ route('admin.delivery.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> {{ __('admin.delivery.add') }}
        </a>
    </div>

    {{-- 📋 Таблица методов доставки --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 border rounded shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🔢</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">📦 {{ __('admin.delivery.name2') }}</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🚚 {{ __('admin.delivery.type_short') }}</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">💰 {{ __('admin.delivery.price_short') }}</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">📅 {{ __('admin.delivery.terms') }}</th>
                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">🎁 {{ __('admin.delivery.free_label') }}</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">🇷🇺 {{ __('admin.delivery.rf') }}</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">🌐 API</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">✅ {{ __('admin.delivery.active') }}</th>
                    <th class="px-6 py-3 text-center font-semibold whitespace-nowrap">⚙️ {{ __('admin.delivery.actions') }}</th>
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
                                    🚚 {{ __('admin.delivery.m_courier') }}
                                    @break
                                @case('pickup')
                                    🛍️ {{ __('admin.delivery.m_pickup') }}
                                    @break
                                @case('post')
                                    📦 {{ __('admin.delivery.m_post') }}
                                    @break
                                @case('terminal')
                                    🏧 {{ __('admin.delivery.m_terminal') }}
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
                                    {{ __('admin.delivery.from') }} {{ number_format($method->free_delivery_threshold, 0, ',', ' ') }} ₽
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
                               class="text-blue-600 hover:text-blue-800 transition" title="{{ __('admin.admin.edit') }}">
                                ✏️
                            </a>

                            <form action="{{ route('admin.delivery.destroy', $method) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm(@js(__('admin.delivery.confirm_delete')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition" title="{{ __('admin.admin.delete') }}">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 {{ __('admin.delivery.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
