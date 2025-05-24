@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    {{-- 🔰 Заголовок и форма установки нового модуля --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🧩 Управление модулями
        </h1>

        {{-- 📥 Установка нового модуля через ZIP --}}
        <form action="{{ route('admin.modules.install') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="module" required class="border border-gray-300 rounded px-2 py-1 text-sm">
            <button type="submit"
                    class="bg-black text-white px-4 py-2 rounded text-sm hover:bg-gray-800 transition">
                ⬆️ Установить
            </button>
        </form>
    </div>

    {{-- 📋 Таблица модулей --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-md overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">📦 Название</th>
                    <th class="py-3 px-4">🧾 Версия</th>
                    <th class="py-3 px-4 text-center">⚙️ Статус</th>
                    <th class="py-3 px-4 text-center">📥 Установлен</th>
                    <th class="py-3 px-4 text-center">🔢 Приоритет</th>
                    <th class="py-3 px-4 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{-- 📦 Название --}}
                        <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                            {{ $module->name }}
                        </td>

                        {{-- 🧾 Версия --}}
                        <td class="py-3 px-4 text-gray-800 dark:text-gray-200">
                            {{ $module->version ?? '—' }}
                        </td>

                        {{-- ⚙️ Статус --}}
                        <td class="py-3 px-4 text-center">
                            @if ($module->active)
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200 rounded-full">
                                    <i class="fas fa-check-circle"></i> Активен
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200 rounded-full">
                                    <i class="fas fa-times-circle"></i> Неактивен
                                </span>
                            @endif
                        </td>

                        {{-- 📥 Установлен --}}
                        <td class="py-3 px-4 text-center">
                            {!! $module->is_installed ? '✅' : '❌' !!}
                        </td>

                        {{-- 🔢 Приоритет --}}
                        <td class="py-3 px-4 text-center">
                            {{ $module->priority ?? 0 }}
                        </td>

                        {{-- ⚙️ Действия --}}
                        <td class="py-3 px-4 text-center space-x-1">
                            {{-- 🔄 Переключение активности --}}
                            <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-xs px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white">
                                    {{ $module->active ? 'Отключить' : 'Включить' }}
                                </button>
                            </form>

                            {{-- 📥 Архивирование (фактически — деактивация + пометка) --}}
                            <form method="POST" action="{{ route('admin.modules.archive', $module->id) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-xs px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white">
                                    Архивировать
                                </button>
                            </form>

                            {{-- ❌ Удаление --}}
                            <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}" class="inline" onsubmit="return confirm('Удалить модуль {{ $module->name }}?')">
                                @csrf @method('DELETE')
                                <button class="text-xs px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Модули не найдены
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
