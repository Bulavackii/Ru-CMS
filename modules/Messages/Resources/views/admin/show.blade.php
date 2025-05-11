@extends('layouts.admin')

@section('title', 'Просмотр сообщения')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Назад --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center text-sm text-gray-600 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Назад к списку
            </a>
        </div>

        {{-- Заголовок --}}
        <div class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-4">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                📨 {{ $message->subject }}
            </h1>

            {{-- Автор и дата --}}
            <div class="text-sm text-gray-600">
                От: <span class="font-semibold">{{ $message->user->name ?? '—' }}</span><br>
                Дата: <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
            </div>

            {{-- Сообщение --}}
            <div class="prose max-w-none text-gray-800">
                {!! nl2br(e($message->body)) !!}
            </div>

            {{-- Статус --}}
            <div class="pt-4 border-t border-gray-100 text-sm text-gray-500">
                Статус:
                @if ($message->is_read)
                    <span class="text-green-600">Прочитано</span>
                @else
                    <span class="text-yellow-600">Не прочитано</span>
                @endif
            </div>
        </div>
    </div>
@endsection
