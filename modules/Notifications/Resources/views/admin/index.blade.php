@extends('layouts.admin')

@section('title', 'Уведомления')

@section('content')
    {{-- 🔔 Заголовок страницы и кнопка --}}
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <i class="fas fa-bell"></i> Уведомления
        </h1>
        <a href="{{ route('admin.notifications.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Добавить
        </a>
    </div>

    {{-- 📋 Таблица уведомлений --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">📌 Заголовок</th>
                    <th class="px-4 py-2 text-left">📋 Тип</th>
                    <th class="px-4 py-2 text-left">🎯 Аудитория</th>
                    <th class="px-4 py-2 text-left">📍 Позиция</th>
                    <th class="px-4 py-2 text-left">⏱️ Время</th>
                    <th class="px-4 py-2 text-left">🗺️ Страница</th>
                    <th class="px-4 py-2 text-center">✅ Вкл.</th>
                    <th class="px-4 py-2 text-center">⚙️ Действия</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($notifications as $notification)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                        {{-- 📝 Заголовок --}}
                        <td class="px-4 py-2 truncate max-w-xs text-gray-800 dark:text-gray-100" title="{{ $notification->title }}">
                            {{ $notification->title }}
                        </td>

                        {{-- 📋 Тип (html, cookie и т.д.) --}}
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            {{ ucfirst($notification->type) }}
                        </td>

                        {{-- 👥 Аудитория (all, admin, user) --}}
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            {{ ucfirst($notification->target) }}
                        </td>

                        {{-- 📍 Позиция (top, bottom, fullscreen) --}}
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            {{ ucfirst($notification->position) }}
                        </td>

                        {{-- ⏱️ Время показа --}}
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            {{ $notification->duration ? $notification->duration . ' сек' : '∞' }}
                        </td>

                        {{-- 🗺️ Страница фильтра --}}
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                            {{ $notification->route_filter ?: 'На всех' }}
                        </td>

                        {{-- ✅ Вкл./Выкл. --}}
                        <td class="px-4 py-2 text-center">
                            <form action="{{ route('admin.notifications.toggle', $notification->id) }}"
                                  method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="{{ $notification->enabled ? 'Отключить' : 'Включить' }}"
                                        class="{{ $notification->enabled ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600' }} text-lg transition">
                                    {{ $notification->enabled ? '🟢' : '⚪' }}
                                </button>
                            </form>
                        </td>

                        {{-- ⚙️ Действия: Редактировать / Удалить --}}
                        <td class="px-4 py-2 text-center whitespace-nowrap space-x-2">
                            {{-- ✏️ Редактировать --}}
                            <a href="{{ route('admin.notifications.edit', $notification->id) }}"
                               class="text-blue-600 hover:text-blue-800 transition" title="Редактировать">
                                ✏️
                            </a>

                            {{-- 🗑️ Удалить --}}
                            <form action="{{ route('admin.notifications.destroy', $notification->id) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Удалить уведомление?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:text-red-800 transition" title="Удалить">
                                    🗑
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    {{-- 📭 Пусто --}}
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Уведомлений пока нет
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
