@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    {{-- 🔰 Заголовок страницы --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🧩 Управление модулями
        </h1>
    </div>

    {{-- 📋 Таблица модулей --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-md overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4 text-left">📦 Название</th>
                    <th class="py-3 px-4 text-left">🧾 Версия</th>
                    <th class="py-3 px-4 text-center">⚙️ Статус</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                            {{ $module->name }}
                        </td>
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                            {{ $module->version }}
                        </td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Модули не найдены
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
