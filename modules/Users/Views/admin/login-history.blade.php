@extends('layouts.admin')

@section('title', 'История входов')

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🕐 История входов</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Пользователь: <strong>{{ $user->name }}</strong> ({{ $user->email }})
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl shadow-md border border-gray-200 dark:border-gray-800">
        <table class="min-w-full bg-white dark:bg-gray-900 text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3">📅 Дата и время</th>
                    <th class="px-4 py-3">🌐 IP адрес</th>
                    <th class="px-4 py-3">🖥️ User Agent</th>
                    <th class="px-4 py-3">📍 Местоположение</th>
                    <th class="px-4 py-3">✅ Статус</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($history as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $entry->created_at->format('d.m.Y H:i:s') }}
                            <br>
                            <span class="text-xs text-gray-500">{{ $entry->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">
                            {{ $entry->ip_address ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs max-w-md truncate">
                            {{ $entry->user_agent ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                            @if($entry->country)
                                {{ $entry->country }}
                                @if($entry->city)
                                    , {{ $entry->city }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($entry->success)
                                <span class="inline-flex items-center gap-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-check"></i> Успешно
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-times"></i> Ошибка
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
                            📭 История входов пуста
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $history->links('vendor.pagination.tailwind') }}
    </div>
@endsection




