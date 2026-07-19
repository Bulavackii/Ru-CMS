@extends('layouts.admin')

@section('title', 'Просмотр сообщения')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- 🔙 Кнопка "Назад к списку сообщений" --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 transition">
                @themeIcon('arrow-left') Назад к списку
            </a>
            <div class="flex items-center gap-2">
                @if($message->to_user_id === Auth::id())
                    <form method="POST" action="{{ route('admin.messages.toggle-read', $message) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 rounded-md">
                            {{ $message->is_read ? 'Пометить непрочитанным' : 'Пометить прочитанным' }}
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.messages.toggle-important', $message) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm {{ $message->is_important ? 'bg-yellow-200 hover:bg-yellow-300' : 'bg-gray-200 hover:bg-gray-300' }} rounded-md">
                        ⭐ {{ $message->is_important ? 'Убрать из важных' : 'Пометить важным' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.messages.archive', $message) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 rounded-md">
                        {{ $message->archived_at ? 'Восстановить' : 'Архивировать' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- 📩 Цепочка переписки --}}
        @if($thread && $thread->count() > 1)
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    📎 Цепочка переписки ({{ $thread->count() }} сообщений)
                </div>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @foreach($thread as $threadMsg)
                        <a href="{{ route('admin.messages.show', $threadMsg) }}" 
                           class="block text-sm {{ $threadMsg->id === $message->id ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                            {{ $threadMsg->subject }} — {{ $threadMsg->sender->name ?? '—' }}, {{ $threadMsg->created_at->format('d.m.Y H:i') }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 📩 Карточка письма --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-6">

            {{-- 📨 Тема сообщения --}}
            <div class="flex items-start justify-between">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    @if($message->is_important)
                        <span class="text-yellow-500">⭐</span>
                    @endif
                    {{ $message->subject }}
                </h1>
                @if($message->parent_id)
                    <a href="{{ route('admin.messages.show', $message->parent) }}" 
                       class="text-sm text-blue-600 hover:text-blue-700">
                        ← К родительскому сообщению
                    </a>
                @endif
            </div>

            {{-- 👤 Автор и дата отправки --}}
            <div class="flex items-center gap-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-lg">
                    {{ strtoupper(substr($message->sender->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <p><strong>Отправитель:</strong> {{ $message->sender->name ?? '—' }} ({{ $message->sender->email ?? '—' }})</p>
                        <p><strong>Получатель:</strong> {{ $message->receiver->name ?? '—' }} ({{ $message->receiver->email ?? '—' }})</p>
                        <p><strong>Дата:</strong> {{ $message->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    @if($message->to_user_id === Auth::id())
                        @if($message->is_read)
                            <span class="inline-flex items-center gap-1 text-green-600 font-semibold text-sm">
                                ✅ Прочитано
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-yellow-600 font-semibold text-sm">
                                🕓 Не прочитано
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- 📎 Вложения --}}
            @if($message->attachments->count() > 0)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📎 Вложения ({{ $message->attachments->count() }})
                    </div>
                    <div class="space-y-2">
                        @foreach($message->attachments as $attachment)
                            <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400">📎</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $attachment->filename }}</span>
                                    <span class="text-xs text-gray-500">({{ $attachment->human_size }})</span>
                                </div>
                                <a href="{{ route('admin.messages.attachment.download', $attachment) }}" 
                                   class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Скачать
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 💬 Содержимое письма --}}
            <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 whitespace-pre-wrap">
                {!! nl2br(e($message->body)) !!}
            </div>

            {{-- 🔗 Быстрые действия --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <a href="{{ route('admin.messages.reply', $message) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-semibold transition">
                    @themeIcon('reply') Ответить
                </a>
                <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" 
                      onsubmit="return confirm('Удалить сообщение?')" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold transition">
                        @themeIcon('trash') Удалить
                    </button>
                </form>
            </div>
        </div>

        {{-- 📬 Ответы на это сообщение --}}
        @if($message->replies->count() > 0)
            <div class="bg-white dark:bg-gray-900 shadow rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                    📬 Ответы ({{ $message->replies->count() }})
                </h2>
                <div class="space-y-4">
                    @foreach($message->replies as $reply)
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm">
                                    <strong>{{ $reply->sender->name ?? '—' }}</strong>
                                    <span class="text-gray-500">— {{ $reply->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <a href="{{ route('admin.messages.show', $reply) }}" 
                                   class="text-sm text-blue-600 hover:text-blue-700">
                                    Просмотр →
                                </a>
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>{{ $reply->subject }}</strong>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ Str::limit(strip_tags($reply->body), 200) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
