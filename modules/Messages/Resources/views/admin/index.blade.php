@extends('layouts.admin')

@section('title', 'Сообщения')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-2">
            📨 Сообщения
        </h1>
        <a href="{{ route('admin.messages.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i> Новое сообщение
        </a>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs border-b">
                <tr>
                    <th class="px-4 py-3 text-left">Тема</th>
                    <th class="px-4 py-3 text-left">Автор</th>
                    <th class="px-4 py-3 text-center">Статус</th>
                    <th class="px-4 py-3 text-right">Дата</th>
                </tr>
            </thead>
            <tbody class="text-gray-800">
                @forelse($messages as $msg)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.messages.show', $msg) }}"
                               class="text-blue-600 hover:underline font-medium">
                                {{ $msg->subject }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $msg->user->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($msg->is_read)
                                <span class="text-green-600">Прочитано</span>
                            @else
                                <span class="text-yellow-600">Не прочитано</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-500">
                            {{ $msg->created_at->format('d.m.Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Сообщений нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $messages->links() }}
    </div>
@endsection
