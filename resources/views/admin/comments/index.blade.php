@extends('layouts.admin')

@section('title', 'Комментарии')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💬 Комментарии</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Модерация комментариев</p>
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="w-full border rounded px-3 py-2" placeholder="Поиск по комментариям...">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Статус</label>
                <select name="status" class="border rounded px-3 py-2">
                    <option value="">Все</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>На модерации</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Одобренные</option>
                    <option value="spam" {{ request('status') === 'spam' ? 'selected' : '' }}>Спам</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                🔍 Поиск
            </button>
        </form>
    </div>

    {{-- Список комментариев --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left"><input type="checkbox" id="select-all"></th>
                        <th class="px-4 py-3 text-left">Автор</th>
                        <th class="px-4 py-3 text-left">Комментарий</th>
                        <th class="px-4 py-3 text-left">Контент</th>
                        <th class="px-4 py-3 text-left">Статус</th>
                        <th class="px-4 py-3 text-left">Дата</th>
                        <th class="px-4 py-3 text-left">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($comments as $comment)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" class="comment-checkbox" value="{{ $comment->id }}">
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $comment->author_name }}</div>
                                <div class="text-sm text-gray-500">{{ $comment->author_email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="max-w-md truncate">{{ Str::limit($comment->content, 100) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-500">
                                    {{ class_basename($comment->model_type) }} #{{ $comment->model_id }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs
                                    {{ $comment->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $comment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $comment->status === 'spam' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $comment->status === 'approved' ? 'Одобрен' : ($comment->status === 'pending' ? 'На модерации' : 'Спам') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $comment->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if($comment->status !== 'approved')
                                        <button onclick="approveComment({{ $comment->id }})" 
                                            class="text-green-600 hover:text-green-800" title="Одобрить">
                                            ✅
                                        </button>
                                    @endif
                                    @if($comment->status !== 'spam')
                                        <button onclick="rejectComment({{ $comment->id }})" 
                                            class="text-red-600 hover:text-red-800" title="Отклонить">
                                            ❌
                                        </button>
                                    @endif
                                    <button onclick="deleteComment({{ $comment->id }})" 
                                        class="text-gray-600 hover:text-gray-800" title="Удалить">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Пагинация --}}
        <div class="p-4">
            {{ $comments->links() }}
        </div>
    </div>

    {{-- Массовые действия --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="flex gap-2">
            <select id="bulk-action" class="border rounded px-3 py-2">
                <option value="">Выберите действие</option>
                <option value="approve">Одобрить</option>
                <option value="reject">Отклонить</option>
                <option value="delete">Удалить</option>
            </select>
            <button onclick="bulkAction()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Применить
            </button>
        </div>
    </div>
</div>

<script>
function approveComment(id) {
    fetch(`/admin/comments/${id}/approve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function rejectComment(id) {
    fetch(`/admin/comments/${id}/reject`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function deleteComment(id) {
    if (!confirm('Удалить комментарий?')) return;
    
    fetch(`/admin/comments/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function bulkAction() {
    const action = document.getElementById('bulk-action').value;
    const selected = Array.from(document.querySelectorAll('.comment-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (!action || selected.length === 0) {
        alert('Выберите действие и комментарии');
        return;
    }

    fetch('/admin/comments/bulk', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            action: action,
            comment_ids: selected
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('.comment-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection

