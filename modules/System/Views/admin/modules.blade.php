@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🧩 Управление модулями</h1>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="py-3 px-4 text-left">📦 Название</th>
                    <th class="py-3 px-4 text-left">🧾 Версия</th>
                    <th class="py-3 px-4 text-center">⚙️ Активен</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="py-3 px-4 text-gray-800 dark:text-gray-100 font-medium">
                            {{ $module->name }}
                        </td>
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                            {{ $module->version }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($module->active)
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200 rounded-full">
                                    ✅ Активен
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200 rounded-full">
                                    ⛔ Неактивен
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
