@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    {{-- 🔹 Заголовок и установка --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🧩 Модули</h1>

        {{-- 📦 Кнопка "Установить модуль" --}}
        <label for="upload-module"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold cursor-pointer transition">
            <i class="fas fa-upload"></i> Установить
        </label>
    </div>

    {{-- 📤 Скрытая форма установки ZIP-модуля --}}
    <form method="POST" action="{{ route('admin.modules.install') }}" enctype="multipart/form-data" id="upload-form" class="hidden">
        @csrf
        <input type="file" name="module" id="upload-module" accept=".zip" class="hidden"
               onchange="document.getElementById('upload-form').submit();">
    </form>

    {{-- 📊 Таблица модулей --}}
    <div class="overflow-x-auto mt-4">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-800 rounded-xl shadow-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">📦 Название</th>
                    <th class="px-4 py-3 text-left">🧾 Версия</th>
                    <th class="px-4 py-3 text-center">📢 Статус</th>
                    <th class="px-4 py-3 text-center">📅 Установлен</th>
                    <th class="px-4 py-3 text-center">🔢 Приоритет</th>
                    <th class="px-4 py-3 text-center">⚙️ Действие</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $module->name }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $module->version }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full
                                {{ $module->active ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200'
                                                   : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200' }}">
                                <i class="fas {{ $module->active ? 'fa-check-circle' : 'fa-power-off' }}"></i>
                                {{ $module->active ? 'Активен' : 'Неактивен' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            {{ $module->installed_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            {{ $module->priority }}
                        </td>
                        <td class="px-4 py-3 text-center flex flex-wrap gap-2 justify-center">
                            {{-- 🔁 Переключение активности --}}
                            <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded text-white
                                           {{ $module->active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                                    <i class="fas {{ $module->active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                    {{ $module->active ? 'Отключить' : 'Включить' }}
                                </button>
                            </form>

                            {{-- 🗑️ Удаление --}}
                            <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}"
                                  onsubmit="return confirm('Удалить модуль {{ $module->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700">
                                    <i class="fas fa-trash-alt"></i> Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Модули не найдены
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
