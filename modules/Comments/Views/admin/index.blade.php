@extends('layouts.admin')

@section('title', 'Модерация комментариев')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Комментарии</h1>
        
        <div class="flex gap-2">
            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" 
               class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                На модерации ({{ \Modules\Comments\Models\Comment::where('status', 'pending')->count() }})
            </a>
            <a href="{{ route('admin.comments.index', ['status' => 'spam']) }}" 
               class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Спам ({{ \Modules\Comments\Models\Comment::where('status', 'spam')->count() }})
            </a>
        </div>
    </div>
    
    {{-- Фильтры --}}
    <form method="GET" class="flex gap-4">
        <select name="status" class="px-4 py-2 border rounded">
            <option value="">Все статусы</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>На модерации</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Одобренные</option>
            <option value="spam" {{ request('status') === 'spam' ? 'selected' : '' }}>Спам</option>
            <option value="trash" {{ request('status') === 'trash' ? 'selected' : '' }}>Удаленные</option>
        </select>
        
        <input type="text" name="search" placeholder="Поиск..." 
               value="{{ request('search') }}"
               class="px-4 py-2 border rounded">
        
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Поиск
        </button>
    </form>
    
    {{-- Список комментариев --}}
    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="select-all">
                    </th>
                    <th class="px-4 py-3 text-left">Автор</th>
                    <th class="px-4 py-3 text-left">Комментарий</th>
                    <th class="px-4 py-3 text-left">К объекту</th>
                    <th class="px-4 py-3 text-left">Статус</th>
                    <th class="px-4 py-3 text-left">Дата</th>
                    <th class="px-4 py-3 text-left">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comments as $comment)
                <tr class="border-t">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="comment-checkbox" value="{{ $comment->id }}">
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <strong>{{ $comment->author_name ?? $comment->user->name ?? 'Гость' }}</strong>
                            <div class="text-sm text-gray-500">{{ $comment->author_email ?? $comment->user->email ?? '' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="max-w-md">{{ Str::limit($comment->content, 100) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm">{{ class_basename($comment->model_type) }} #{{ $comment->model_id }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs
                            {{ $comment->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $comment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $comment->status === 'spam' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $comment->status === 'trash' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ $comment->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $comment->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            @if($comment->status !== 'approved')
                                <button onclick="approveComment({{ $comment->id }})" 
                                        class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">
                                    Одобрить
                                </button>
                            @endif
                            @if($comment->status !== 'spam')
                                <button onclick="spamComment({{ $comment->id }})" 
                                        class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">
                                    Спам
                                </button>
                            @endif
                            <button onclick="rejectComment({{ $comment->id }})" 
                                    class="px-2 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600">
                                Удалить
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{ $comments->links() }}
    
    {{-- Массовые действия --}}
    <div class="flex gap-2">
        <select id="bulk-action" class="px-4 py-2 border rounded">
            <option value="">Выберите действие</option>
            <option value="approve">Одобрить</option>
            <option value="reject">Удалить</option>
            <option value="spam">Пометить как спам</option>
        </select>
        <button onclick="bulkAction()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Применить
        </button>
    </div>
</div>

<script>
function approveComment(id) {
    fetch(`/admin/comments/${id}/approve`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function spamComment(id) {
    fetch(`/admin/comments/${id}/spam`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function rejectComment(id) {
    if (!confirm('Удалить комментарий?')) return;
    fetch(`/admin/comments/${id}/reject`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function bulkAction() {
    const action = document.getElementById('bulk-action').value;
    const ids = Array.from(document.querySelectorAll('.comment-checkbox:checked')).map(c => c.value);
    
    if (!action || ids.length === 0) {
        alert('Выберите действие и комментарии');
        return;
    }
    
    fetch('/admin/comments/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ action, ids })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}
</script>
@endsection

