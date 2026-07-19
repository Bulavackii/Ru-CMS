@extends('layouts.admin')

@section('title', isset($replyTo) ? 'Ответить на сообщение' : 'Новое сообщение')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0 space-y-6">

        {{-- 🔙 Назад --}}
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center hover:text-blue-600 dark:hover:text-blue-400 transition">
                @themeIcon('arrow-left') Назад к сообщениям
            </a>
        </div>

        {{-- 📝 Форма создания сообщения --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow px-6 py-8 space-y-6">

            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                📝 {{ isset($replyTo) ? 'Ответить на сообщение' : 'Новое сообщение' }}
            </h1>

            @if(isset($replyTo))
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        Ответ на сообщение:
                    </div>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>{{ $replyTo->subject }}</strong> от {{ $replyTo->sender->name ?? '—' }}
                    </div>
                </div>
            @endif

            {{-- 🔴 Ошибки валидации --}}
            @if ($errors->any())
                <div class="bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded shadow">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.messages.store') }}" 
                  enctype="multipart/form-data" 
                  id="message-form"
                  class="space-y-6">
                @csrf

                @if(isset($replyTo))
                    <input type="hidden" name="parent_id" value="{{ $replyTo->id }}">
                @endif

                {{-- 👤 Кому --}}
                <div>
                    <label for="to_user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        👤 Получатель *
                    </label>
                    <select name="to_user_id" id="to_user_id" required
                            class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('to_user_id') border-red-500 @enderror">
                        <option value="">-- Выберите администратора --</option>
                        @foreach ($admins as $admin)
                            @if($admin->id !== Auth::id())
                                <option value="{{ $admin->id }}" 
                                        @selected((isset($recipient) && $recipient->id === $admin->id) || old('to_user_id') == $admin->id)>
                                    {{ $admin->name }} ({{ $admin->email }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('to_user_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 🏷️ Тема --}}
                <div>
                    <label for="subject" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        🏷️ Тема сообщения *
                    </label>
                    <input type="text" name="subject" id="subject" required
                           value="{{ isset($replyTo) ? 'Re: ' . $replyTo->subject : old('subject') }}"
                           class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('subject') border-red-500 @enderror">
                    @error('subject')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 💬 Текст --}}
                <div>
                    <label for="body" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        💬 Сообщение *
                    </label>
                    <textarea name="body" id="body" rows="10" required
                              placeholder="Введите сообщение для других админов..."
                              class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('body') border-red-500 @enderror">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div class="mt-1 text-xs text-gray-500">
                        <span id="char-count">0</span> символов
                    </div>
                </div>

                {{-- 📎 Вложения --}}
                <div>
                    <label for="attachments" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        📎 Вложения (максимум 10MB на файл)
                    </label>
                    <input type="file" name="attachments[]" id="attachments" multiple
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar"
                           class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400">
                    <div class="mt-1 text-xs text-gray-500">
                        Можно выбрать несколько файлов. Максимальный размер: 10MB на файл.
                    </div>
                    <div id="file-list" class="mt-2 space-y-1"></div>
                </div>

                {{-- ⭐ Важное сообщение --}}
                <div>
                    <label class="inline-flex items-center gap-3 select-none cursor-pointer">
                        <input type="checkbox" name="is_important" value="1"
                               {{ old('is_important') ? 'checked' : '' }}
                               class="peer sr-only">
                        <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 peer-checked:bg-yellow-500 transition-all">
                            <span class="absolute left-1 peer-checked:left-6 h-4 w-4 rounded-full bg-white transition-all"></span>
                        </span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">⭐ Пометить как важное</span>
                    </label>
                </div>

                {{-- 📤 Кнопка отправки --}}
                <div class="flex justify-end gap-3">
                    <button type="button" id="save-draft" 
                            class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-md shadow transition">
                        💾 Сохранить черновик
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow transition">
                        @themeIcon('paper-plane') Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Счётчик символов
    const bodyTextarea = document.getElementById('body');
    const charCount = document.getElementById('char-count');
    
    function updateCharCount() {
        charCount.textContent = bodyTextarea.value.length;
    }
    
    bodyTextarea.addEventListener('input', updateCharCount);
    updateCharCount();

    // Показ выбранных файлов
    const fileInput = document.getElementById('attachments');
    const fileList = document.getElementById('file-list');
    
    fileInput.addEventListener('change', function() {
        fileList.innerHTML = '';
        if (this.files.length > 0) {
            Array.from(this.files).forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'text-sm text-gray-600 dark:text-gray-400 flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded';
                div.innerHTML = `
                    <span>📎 ${file.name}</span>
                    <span class="text-xs">${(file.size / 1024).toFixed(2)} KB</span>
                `;
                fileList.appendChild(div);
            });
        }
    });

    // Автосохранение черновика в localStorage
    const form = document.getElementById('message-form');
    const draftKey = 'message_draft_' + (new Date().toDateString());
    
    // Загрузка черновика
    function loadDraft() {
        const draft = localStorage.getItem(draftKey);
        if (draft) {
            try {
                const data = JSON.parse(draft);
                if (confirm('Найден черновик сообщения. Загрузить?')) {
                    document.getElementById('to_user_id').value = data.to_user_id || '';
                    document.getElementById('subject').value = data.subject || '';
                    document.getElementById('body').value = data.body || '';
                    updateCharCount();
                }
            } catch (e) {
                console.error('Ошибка загрузки черновика:', e);
            }
        }
    }
    
    // Сохранение черновика
    function saveDraft() {
        const draft = {
            to_user_id: document.getElementById('to_user_id').value,
            subject: document.getElementById('subject').value,
            body: document.getElementById('body').value,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem(draftKey, JSON.stringify(draft));
    }
    
    // Автосохранение каждые 30 секунд
    setInterval(saveDraft, 30000);
    
    // Сохранение при изменении
    ['to_user_id', 'subject', 'body'].forEach(id => {
        document.getElementById(id).addEventListener('input', saveDraft);
    });
    
    // Очистка черновика при отправке
    form.addEventListener('submit', () => {
        localStorage.removeItem(draftKey);
    });
    
    // Кнопка сохранения черновика
    document.getElementById('save-draft').addEventListener('click', () => {
        saveDraft();
        alert('Черновик сохранён!');
    });
    
    // Загрузка при загрузке страницы
    loadDraft();
</script>
@endpush
