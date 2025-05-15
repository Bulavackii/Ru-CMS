@extends('layouts.admin')

@section('title', 'Способы оплаты')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">💳 Способы оплаты</h1>
        <a href="{{ route('admin.payments.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Добавить
        </a>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded shadow border dark:border-gray-800">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">Название</th>
                    <th class="px-6 py-3 font-semibold">Тип</th>
                    <th class="px-6 py-3 font-semibold text-center">Статус</th>
                    <th class="px-6 py-3 font-semibold text-center">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($methods as $method)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $method->title }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300 capitalize">
                            {{ $method->type === 'online' ? '💻 Онлайн' : '🏦 Офлайн' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if ($method->active)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-semibold">
                                    <i class="fas fa-check-circle"></i> Включен
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-800 text-xs font-semibold">
                                    <i class="fas fa-times-circle"></i> Выключен
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <a href="{{ route('admin.payments.edit', $method->id) }}"
                               class="inline-flex items-center gap-1 text-blue-600 text-sm font-medium">
                                ✏️
                            </a>
                            <form action="{{ route('admin.payments.destroy', $method->id) }}" method="POST"
                                  class="inline" onsubmit="return confirm('Удалить этот способ оплаты?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 text-red-600 text-sm font-medium">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            🤷 Нет способов оплаты.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
