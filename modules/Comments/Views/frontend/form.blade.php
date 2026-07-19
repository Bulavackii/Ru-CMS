{{-- Форма добавления комментария с каптчей --}}
<div class="comment-form mt-6">
    <h3 class="text-xl font-semibold mb-4">Добавить комментарий</h3>
    
    <form id="comment-form" action="{{ route('api.comments.store') }}" method="POST">
        @csrf
        
        <input type="hidden" name="model_type" value="{{ $modelType }}">
        <input type="hidden" name="model_id" value="{{ $modelId }}">
        <input type="hidden" name="parent_id" value="{{ $parentId ?? null }}">
        
        @guest
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Имя *</label>
                <input type="text" name="author_name" required 
                       class="w-full px-4 py-2 border rounded-lg">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email *</label>
                <input type="email" name="author_email" required 
                       class="w-full px-4 py-2 border rounded-lg">
            </div>
        @endguest
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Комментарий *</label>
            <textarea name="content" required rows="5" 
                      class="w-full px-4 py-2 border rounded-lg"
                      placeholder="Ваш комментарий..."></textarea>
        </div>
        
        {{-- Каптча для гостей --}}
        @guest
            @if(config('captcha.enabled', true))
                <div class="mb-4" id="captcha-container">
                    {!! captcha_img(config('captcha.default_type', 'image')) !!}
                </div>
            @endif
        @endguest
        
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Отправить
        </button>
    </form>
</div>

<script>
document.getElementById('comment-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            this.reset();
            // Обновить список комментариев
            if (typeof refreshComments === 'function') {
                refreshComments();
            }
            // Обновить каптчу
            if (document.getElementById('captcha-container')) {
                fetch('{{ route("api.captcha.generate", ["type" => config("captcha.default_type", "image")]) }}')
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('captcha-container').innerHTML = data.html;
                    });
            }
        } else {
            alert(data.message || 'Ошибка при отправке комментария');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ошибка при отправке комментария');
    }
});
</script>

