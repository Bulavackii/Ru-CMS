@extends('layouts.admin')

@section('title', 'Способы оплаты')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">💳 Способы оплаты</h1>
        <a href="{{ route('admin.payments.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold">
            <i class="fas fa-plus"></i> Добавить
        </a>
    </div>

    <table class="min-w-full bg-white dark:bg-gray-900 rounded shadow overflow-hidden">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
            <tr>
                <th class="px-4 py-3 text-left">Название</th>
                <th class="px-4 py-3 text-left">Тип</th>
                <th class="px-4 py-3 text-left">Активность</th>
                <th class="px-4 py-3 text-left">Действия</th>
            </tr>
        </thead>
        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($methods as $method)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $method->title }}</td>
                    <td class="px-4 py-3">{{ $method->type }}</td>
                    <td class="px-4 py-3">
                        @if ($method->active)
                            <span class="text-green-600">Вкл</span>
                        @else
                            <span class="text-red-600">Выкл</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 space-x-2">
                        <a href="{{ route('admin.payments.edit', $method->id) }}"
                           class="text-blue-600 hover:underline">✏️ Ред.</a>
                        <form action="{{ route('admin.payments.destroy', $method->id) }}"
                              method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Удалить?')" class="text-red-600 hover:underline">
                                🗑️ Удалить
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Нет способов оплаты.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
