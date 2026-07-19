@props(['comment', 'level' => 0])

<div class="comment-item border-l-2 border-gray-200 dark:border-gray-700 pl-4 {{ $level > 0 ? 'ml-4' : '' }}" 
     style="margin-left: {{ $level * 2 }}rem;">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-2">
        <div class="flex items-start justify-between mb-2">
            <div>
                <div class="font-semibold">{{ $comment->author_name }}</div>
                <div class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</div>
            </div>
            <div class="flex gap-2 text-sm">
                <button onclick="likeComment({{ $comment->id }})" class="text-blue-600 hover:text-blue-800">
                    👍 {{ $comment->likes }}
                </button>
                <button onclick="dislikeComment({{ $comment->id }})" class="text-red-600 hover:text-red-800">
                    👎 {{ $comment->dislikes }}
                </button>
            </div>
        </div>
        <div class="text-gray-700 dark:text-gray-300 mb-3">
            {{ $comment->content }}
        </div>
        @if($level < 2)
            <button onclick="showReplyForm({{ $comment->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                Ответить
            </button>
        @endif
    </div>

    {{-- Форма ответа --}}
    <div id="reply-form-{{ $comment->id }}" class="hidden mb-4">
        <form onsubmit="submitReply(event, {{ $comment->id }})" class="space-y-2">
            @csrf
            <input type="hidden" name="model_type" value="{{ request('model_type') ?? get_class($comment->commentable) }}">
            <input type="hidden" name="model_id" value="{{ request('model_id') ?? $comment->commentable->id }}">
            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
            <textarea name="content" rows="3" required 
                class="w-full border rounded px-3 py-2 text-sm"
                placeholder="Ваш ответ..."></textarea>
            <div class="flex gap-2">
                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                    Отправить
                </button>
                <button type="button" onclick="hideReplyForm({{ $comment->id }})" class="px-3 py-1 bg-gray-200 rounded text-sm">
                    Отмена
                </button>
            </div>
        </form>
    </div>

    {{-- Ответы --}}
    @if($comment->replies->count() > 0)
        <div class="mt-2">
            @foreach($comment->replies as $reply)
                @include('components.comment-item', ['comment' => $reply, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

<script>
function showReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).classList.remove('hidden');
}

function hideReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).classList.add('hidden');
}

function submitReply(e, parentId) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    fetch('/comments', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    });
}
</script>

