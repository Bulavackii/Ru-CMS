@extends('layouts.admin')

@section('title', 'Просмотр сообщения')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- 🔙 Кнопка "Назад к списку сообщений" --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Назад к списку
            </a>
        </div>

        {{-- 📩 Карточка письма --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-6">

            {{-- 📨 Тема сообщения --}}
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                📨 {{ $message->subject }}
            </h1>

            {{-- 👤 Автор и дата отправки --}}
            <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                <p>👤 <strong>Отправитель:</strong> {{ $message->sender->name ?? '—' }}</p>
                <p>🗓️ <strong>Дата:</strong> {{ $message->created_at->format('d.m.Y H:i') }}</p>
            </div>

            {{-- 💬 Содержимое письма --}}
            <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100">
                {!! nl2br(e($message->body)) !!}
            </div>

            {{-- 📬 Статус --}}
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                📌 <strong>Статус:</strong>
                @if ($message->is_read)
                    <span class="text-green-600 dark:text-green-400 font-semibold">✅ Прочитано</span>
                @else
                    <span class="text-yellow-600 dark:text-yellow-400 font-semibold">🕓 Не прочитано</span>
                @endif
            </div>
        </div>
    </div>
@endsection
