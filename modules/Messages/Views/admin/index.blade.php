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
            @themeIcon('plus') Новое сообщение
        </a>
    </div>

    {{-- 🔍 Поиск и фильтры --}}
    <div class="bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
        <form method="GET" action="{{ route('admin.messages.index') }}" class="flex flex-col sm:flex-row gap-4">
            {{-- Поиск --}}
            <div class="flex-1">
                <input type="text" name="search" value="{{ $search ?? '' }}" 
                       placeholder="Поиск по теме и содержимому..."
                       class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                @themeIcon('search') Поиск
            </button>
            @if($search ?? '')
                <a href="{{ route('admin.messages.index', ['filter' => $filter ?? 'all']) }}" 
                   class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 text-sm">
                    Сбросить
                </a>
            @endif
        </form>
    </div>

    {{-- 📑 Вкладки фильтров --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8 overflow-x-auto">
            <a href="{{ route('admin.messages.index', ['filter' => 'all'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Все ({{ $counts['all'] ?? 0 }})
            </a>
            <a href="{{ route('admin.messages.index', ['filter' => 'inbox'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'inbox' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                📥 Входящие ({{ $counts['inbox'] ?? 0 }})
            </a>
            <a href="{{ route('admin.messages.index', ['filter' => 'sent'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'sent' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                📤 Исходящие ({{ $counts['sent'] ?? 0 }})
            </a>
            <a href="{{ route('admin.messages.index', ['filter' => 'unread'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'unread' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                🕓 Непрочитанные ({{ $counts['unread'] ?? 0 }})
            </a>
            <a href="{{ route('admin.messages.index', ['filter' => 'important'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'important' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                ⭐ Важные ({{ $counts['important'] ?? 0 }})
            </a>
            <a href="{{ route('admin.messages.index', ['filter' => 'archived'] + ($search ? ['search' => $search] : [])) }}"
               class="px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition
                      {{ ($filter ?? 'all') === 'archived' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                📦 Архив
            </a>
        </nav>
    </div>

    {{-- 📦 Массовые операции --}}
    <div id="bulk-actions" class="mb-4 hidden bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <form method="POST" action="{{ route('admin.messages.bulk') }}" id="bulk-form">
            @csrf
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Выбрано: <span id="selected-count">0</span>
                </span>
                <select name="action" class="border rounded-md px-3 py-1.5 text-sm dark:bg-gray-800 dark:text-white">
                    <option value="read">Пометить как прочитанное</option>
                    <option value="unread">Пометить как непрочитанное</option>
                    <option value="important">Пометить как важное</option>
                    <option value="unimportant">Убрать из важных</option>
                    <option value="archive">Архивировать</option>
                    <option value="unarchive">Восстановить из архива</option>
                    <option value="delete">Удалить</option>
                </select>
                <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                    Применить
                </button>
                <button type="button" id="clear-selection" class="px-4 py-1.5 bg-gray-400 text-white rounded-md hover:bg-gray-500 text-sm">
                    Снять выбор
                </button>
            </div>
        </form>
    </div>

    {{-- 🧾 Список сообщений --}}
    <div class="space-y-3">
        @forelse($messages as $msg)
            <div class="bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition
                        {{ !$msg->is_read && $msg->to_user_id === Auth::id() ? 'border-l-4 border-l-blue-500' : '' }}
                        {{ $msg->is_important ? 'border-l-4 border-l-yellow-500' : '' }}">
                <div class="flex items-start gap-4">
                    {{-- Чекбокс для массовых операций --}}
                    <input type="checkbox" name="message_ids[]" value="{{ $msg->id }}" 
                           class="message-checkbox mt-1 rounded border-gray-300">
                    
                    {{-- Аватар отправителя --}}
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr($msg->sender->name ?? '?', 0, 1)) }}
                        </div>
                    </div>

                    {{-- Содержимое --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <a href="{{ route('admin.messages.show', $msg) }}" 
                                       class="font-semibold text-gray-900 dark:text-white hover:text-blue-600 truncate">
                                        {{ $msg->subject }}
                                    </a>
                                    @if($msg->is_important)
                                        <span class="text-yellow-500" title="Важное">⭐</span>
                                    @endif
                                    @if($msg->attachments->count() > 0)
                                        <span class="text-gray-400" title="Есть вложения">📎</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    <span class="font-medium">{{ $msg->sender->name ?? '—' }}</span>
                                    <span class="mx-1">→</span>
                                    <span class="font-medium">{{ $msg->receiver->name ?? '—' }}</span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-500 line-clamp-2">
                                    {{ Str::limit(strip_tags($msg->body), 150) }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ $msg->created_at->format('d.m.Y H:i') }}
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($msg->to_user_id === Auth::id())
                                        @if($msg->is_read)
                                            <span class="text-xs text-green-600">✅</span>
                                        @else
                                            <span class="text-xs text-yellow-600 font-bold">🕓</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Быстрые действия --}}
                    <div class="flex-shrink-0 flex items-center gap-1">
                        <a href="{{ route('admin.messages.show', $msg) }}" 
                           class="p-2 text-gray-400 hover:text-blue-600" title="Просмотр">
                            @themeIcon('eye')
                        </a>
                        <a href="{{ route('admin.messages.reply', $msg) }}" 
                           class="p-2 text-gray-400 hover:text-green-600" title="Ответить">
                            @themeIcon('reply')
                        </a>
                        <form method="POST" action="{{ route('admin.messages.toggle-important', $msg) }}" class="inline">
                            @csrf
                            <button type="submit" class="p-2 text-gray-400 hover:text-yellow-600" title="Важное">
                                @themeIcon('star')
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.messages.destroy', $msg) }}" 
                              onsubmit="return confirm('Удалить сообщение?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Удалить">
                                @themeIcon('trash')
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 rounded-xl p-12 text-center">
                <div class="text-gray-500 dark:text-gray-400 text-lg mb-2">
                    📭 Сообщений нет
                </div>
                <a href="{{ route('admin.messages.create') }}" 
                   class="text-blue-600 hover:text-blue-700 text-sm">
                    Создать первое сообщение
                </a>
            </div>
        @endforelse
    </div>

    {{-- 📄 Пагинация --}}
    @if($messages->hasPages())
        <div class="mt-6">
            {{ $messages->links('vendor.pagination.tailwind') }}
        </div>
    @endif
@endsection

@push('scripts')
<script>
    // Массовые операции
    const checkboxes = document.querySelectorAll('.message-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const bulkForm = document.getElementById('bulk-form');
    const clearBtn = document.getElementById('clear-selection');

    function updateBulkActions() {
        const checked = document.querySelectorAll('.message-checkbox:checked');
        const count = checked.length;
        
        if (count > 0) {
            bulkActions.classList.remove('hidden');
            selectedCount.textContent = count;
            
            // Добавляем скрытые поля с ID выбранных сообщений
            const existingInputs = bulkForm.querySelectorAll('input[name="ids[]"]');
            existingInputs.forEach(input => input.remove());
            
            checked.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = checkbox.value;
                bulkForm.appendChild(input);
            });
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    clearBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
        updateBulkActions();
    });

    // Выбрать все
    const selectAllBtn = document.createElement('button');
    selectAllBtn.type = 'button';
    selectAllBtn.className = 'px-3 py-1 text-sm text-gray-600 hover:text-gray-800';
    selectAllBtn.textContent = 'Выбрать все';
    selectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
        updateBulkActions();
    });
    
    if (bulkActions) {
        bulkActions.querySelector('.flex').prepend(selectAllBtn);
    }
</script>
@endpush
