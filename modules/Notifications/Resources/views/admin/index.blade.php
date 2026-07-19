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

    {{-- 🔍 Поиск и фильтры --}}
    <form method="GET" action="{{ route('admin.notifications.index') }}" class="mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Поиск по заголовку или содержимому..."
                       class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <select name="type" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Все типы</option>
                    <option value="text" {{ request('type') === 'text' ? 'selected' : '' }}>Текст</option>
                    <option value="html" {{ request('type') === 'html' ? 'selected' : '' }}>HTML</option>
                    <option value="cookie" {{ request('type') === 'cookie' ? 'selected' : '' }}>Cookie</option>
                </select>
            </div>
            <div>
                <select name="target" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Все аудитории</option>
                    <option value="all" {{ request('target') === 'all' ? 'selected' : '' }}>Все</option>
                    <option value="admin" {{ request('target') === 'admin' ? 'selected' : '' }}>Админы</option>
                    <option value="user" {{ request('target') === 'user' ? 'selected' : '' }}>Пользователи</option>
                </select>
            </div>
            <div>
                <select name="enabled" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Все статусы</option>
                    <option value="1" {{ request('enabled') === '1' ? 'selected' : '' }}>Включено</option>
                    <option value="0" {{ request('enabled') === '0' ? 'selected' : '' }}>Отключено</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                🔍 Применить фильтры
            </button>
            <a href="{{ route('admin.notifications.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                🗑️ Сбросить
            </a>
        </div>
    </form>

    {{-- 📦 Массовые действия --}}
    <form method="POST" action="{{ route('admin.notifications.bulk') }}" id="bulk-form" class="mb-4">
        @csrf
        <div class="flex gap-2 items-center">
            <select name="action" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                <option value="">Выберите действие</option>
                <option value="enable">Включить</option>
                <option value="disable">Отключить</option>
                <option value="delete">Удалить</option>
            </select>
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Применить к выбранным
            </button>
        </div>

    {{-- 📋 Таблица уведомлений --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-4 py-2 text-center">
                        <input type="checkbox" id="select-all" class="bulk-checkbox">
                    </th>
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
                        {{-- ☑️ Чекбокс для массовых действий --}}
                        <td class="px-4 py-2">
                            <input type="checkbox" name="selected[]" value="{{ $notification->id }}" class="bulk-checkbox">
                        </td>
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
                        <td colspan="9" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Уведомлений пока нет
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Пагинация --}}
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

    {{-- Скрипт для массового выбора --}}
    <script>
        document.getElementById('select-all')?.addEventListener('change', function() {
            document.querySelectorAll('.bulk-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    </script>
@endsection
