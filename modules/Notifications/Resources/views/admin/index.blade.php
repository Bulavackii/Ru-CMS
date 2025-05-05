@extends('layouts.admin')

@section('title', 'Уведомления')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Уведомления</h1>
        <a href="{{ route('admin.notifications.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            + Добавить уведомление
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded overflow-hidden text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Заголовок</th>
                <th class="px-4 py-2">Тип</th>
                <th class="px-4 py-2">Аудитория</th>
                <th class="px-4 py-2">Позиция</th>
                <th class="px-4 py-2">Время</th>
                <th class="px-4 py-2">Страница</th>
                <th class="px-4 py-2">Вкл.</th>
                <th class="px-4 py-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($notifications as $notification)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-2 max-w-xs truncate" title="{{ $notification->title }}">
                        {{ $notification->title }}
                    </td>
                    <td class="px-4 py-2">{{ ucfirst($notification->type) }}</td>
                    <td class="px-4 py-2">{{ ucfirst($notification->target) }}</td>
                    <td class="px-4 py-2">{{ ucfirst($notification->position) }}</td>
                    <td class="px-4 py-2">
                        {{ $notification->duration ? $notification->duration . ' сек' : '∞' }}
                    </td>
                    <td class="px-4 py-2">
                        {{ $notification->route_filter ?? 'На всех' }}
                    </td>
                    <td class="px-4 py-2">
                        <form action="{{ route('admin.notifications.toggle', $notification->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" title="{{ $notification->enabled ? 'Отключить' : 'Включить' }}"
                                class="{{ $notification->enabled ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600' }}">
                                {{ $notification->enabled ? '🟢' : '⚪' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-2 space-x-2 whitespace-nowrap">
                        <a href="{{ route('admin.notifications.edit', $notification->id) }}"
                           class="text-blue-600 hover:underline" title="Редактировать">✏️</a>

                        <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST"
                              class="inline" onsubmit="return confirm('Удалить уведомление?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline" title="Удалить">🗑</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 py-4">Нет уведомлений</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
