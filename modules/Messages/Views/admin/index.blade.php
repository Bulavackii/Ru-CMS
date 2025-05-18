@extends('layouts.admin')

@section('title', 'Сообщения')

@section('content')
    {{-- 🔝 Заголовок и кнопка создания --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-800 dark:text-white flex items-center gap-2">
            📨 Сообщения
        </h1>
        <a href="{{ route('admin.messages.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md shadow-md hover:bg-blue-700 transition text-sm font-semibold">
            <i class="fas fa-plus mr-2"></i> Новое сообщение
        </a>
    </div>

    {{-- 🧾 Таблица сообщений --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 rounded-xl">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">📝 Тема</th>
                    <th class="px-4 py-3 text-left">📨 Отправитель → Получатель</th>
                    <th class="px-4 py-3 text-center">📬 Статус</th>
                    <th class="px-4 py-3 text-right">🕒 Дата</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                @forelse($messages as $msg)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{-- 📝 Тема письма --}}
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.messages.show', $msg) }}"
                               class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $msg->subject }}
                            </a>
                        </td>

                        {{-- 📤 Отправитель и получатель --}}
                        <td class="px-4 py-3">
                            {{ $msg->sender->name ?? '—' }}
                            <span class="text-gray-400">→</span>
                            {{ $msg->receiver->name ?? '—' }}
                        </td>

                        {{-- 📬 Статус: прочитано / не прочитано --}}
                        <td class="px-4 py-3 text-center">
                            @if ($msg->is_read)
                                <span class="inline-flex items-center gap-1 text-green-600 font-semibold">
                                    ✅ Прочитано
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-yellow-600 font-semibold">
                                    🕓 Не прочитано
                                </span>
                            @endif
                        </td>

                        {{-- 🕒 Время создания --}}
                        <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                            {{ $msg->created_at->format('d.m.Y H:i') }}
                        </td>
                    </tr>
                @empty
                    {{-- 🔕 Пусто --}}
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Сообщений нет.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $messages->links('vendor.pagination.tailwind') }}
    </div>
@endsection
