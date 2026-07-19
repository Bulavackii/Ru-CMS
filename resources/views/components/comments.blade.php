@props(['model', 'modelType' => null, 'modelId' => null])

@php
    $modelType = $modelType ?? get_class($model);
    $modelId = $modelId ?? $model->id;
    $comments = \Modules\Comments\Models\Comment::with(['user', 'replies.user', 'replies.replies.user'])
        ->where('model_type', $modelType)
        ->where('model_id', $modelId)
        ->where('status', 'approved')
        ->whereNull('parent_id')
        ->orderByDesc('created_at')
        ->get();
@endphp

<div class="comments-section" data-model-type="{{ $modelType }}" data-model-id="{{ $modelId }}">
    <h3 class="text-2xl font-bold mb-4">💬 Комментарии ({{ $comments->count() }})</h3>

    {{-- Форма добавления комментария --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form id="comment-form" class="space-y-4">
            @csrf
            <input type="hidden" name="model_type" value="{{ $modelType }}">
            <input type="hidden" name="model_id" value="{{ $modelId }}">
            
            <div>
                <label class="block text-sm font-medium mb-1">Ваш комментарий</label>
                <textarea name="content" rows="4" required 
                    class="w-full border rounded px-3 py-2"
                    placeholder="Напишите ваш комментарий..."></textarea>
            </div>

            @guest
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Имя</label>
                    <input type="text" name="author_name" required 
                        class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="author_email" required 
                        class="w-full border rounded px-3 py-2">
                </div>
            </div>
            @endguest

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Отправить комментарий
            </button>
        </form>
    </div>

    {{-- Список комментариев --}}
    <div class="space-y-4" id="comments-list">
        @foreach($comments as $comment)
            @include('components.comment-item', ['comment' => $comment, 'level' => 0])
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.getElementById('comment-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Ошибка: ' + (result.message || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
});

function likeComment(commentId) {
    fetch(`/comments/${commentId}/like`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function dislikeComment(commentId) {
    fetch(`/comments/${commentId}/dislike`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endpush

