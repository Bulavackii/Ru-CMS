@extends('layouts.admin')

@section('title', 'Редактировать уведомление')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Редактировать уведомление</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.update', $notification->id) }}" class="space-y-6 max-w-2xl">
        @csrf
        @method('PUT')

        {{-- Заголовок --}}
        <div>
            <label for="title" class="block font-semibold mb-1">Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title', $notification->title) }}" required class="w-full border rounded px-3 py-2">
        </div>

        {{-- Тип --}}
        <div>
            <label for="type" class="block font-semibold mb-1">Тип уведомления</label>
            <select name="type" id="type" class="w-full border rounded px-3 py-2">
                <option value="text" {{ old('type', $notification->type) === 'text' ? 'selected' : '' }}>Текст</option>
                <option value="cookie" {{ old('type', $notification->type) === 'cookie' ? 'selected' : '' }}>Cookie</option>
            </select>
        </div>

        {{-- Аудитория --}}
        <div>
            <label for="target" class="block font-semibold mb-1">Показать для</label>
            <select name="target" id="target" class="w-full border rounded px-3 py-2">
                <option value="all" {{ old('target', $notification->target) === 'all' ? 'selected' : '' }}>Все</option>
                <option value="admin" {{ old('target', $notification->target) === 'admin' ? 'selected' : '' }}>Только админы</option>
                <option value="user" {{ old('target', $notification->target) === 'user' ? 'selected' : '' }}>Только пользователи</option>
            </select>
        </div>

        {{-- Позиция --}}
        <div>
            <label for="position" class="block font-semibold mb-1">Позиция</label>
            <select name="position" id="position" class="w-full border rounded px-3 py-2">
                <option value="top" {{ old('position', $notification->position) === 'top' ? 'selected' : '' }}>Сверху</option>
                <option value="bottom" {{ old('position', $notification->position) === 'bottom' ? 'selected' : '' }}>Снизу</option>
                <option value="fullscreen" {{ old('position', $notification->position) === 'fullscreen' ? 'selected' : '' }}>Во весь экран</option>
            </select>
        </div>

        {{-- Иконка --}}
        <div>
            <label for="icon" class="block font-semibold mb-1">Иконка</label>
            <input type="text" name="icon" id="icon" value="{{ old('icon', $notification->icon) }}" class="w-full border rounded px-3 py-2">
        </div>

        {{-- Цвет фона --}}
        <div>
            <label for="bg_color" class="block font-semibold mb-1">Цвет фона (HEX)</label>
            <input type="text" name="bg_color" id="bg_color" value="{{ old('bg_color', $notification->bg_color) }}" class="w-full border rounded px-3 py-2">
        </div>

        {{-- Цвет текста --}}
        <div>
            <label for="text_color" class="block font-semibold mb-1">Цвет текста (HEX)</label>
            <input type="text" name="text_color" id="text_color" value="{{ old('text_color', $notification->text_color) }}" class="w-full border rounded px-3 py-2">
        </div>

        {{-- Содержимое --}}
        <div>
            <label for="message" class="block font-semibold mb-1">Содержимое</label>
            <textarea name="message" id="editor" rows="6" class="w-full border rounded px-3 py-2">{{ old('message', $notification->message) }}</textarea>
        </div>

        {{-- Время показа --}}
        <div>
            <label for="duration" class="block font-semibold mb-1">⏰ Время показа (секунды)</label>
            <input type="number" name="duration" id="duration" value="{{ old('duration', $notification->duration) }}" class="w-full border rounded px-3 py-2" placeholder="0 = до закрытия">
        </div>

        {{-- Маршрут --}}
        <div>
            <label for="route_filter" class="block font-semibold mb-1">Маршрут или URL</label>
            <input type="text" name="route_filter" id="route_filter" value="{{ old('route_filter', $notification->route_filter) }}" class="w-full border rounded px-3 py-2">
        </div>

        {{-- Ключ cookie --}}
        <div>
            <label for="cookie_key" class="block font-semibold mb-1">Ключ cookie (если нужно)</label>
            <input type="text" name="cookie_key" id="cookie_key" value="{{ old('cookie_key', $notification->cookie_key) }}" class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            💾 Обновить
        </button>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 400,
            branding: false,
            convert_urls: false,
            plugins: [
                'image', 'media', 'mediaembed', 'link', 'lists', 'table', 'code', 'visualblocks', 'wordcount'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
                'link image media mediaembed table | code | removeformat',
            file_picker_types: 'image media',
            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', meta.filetype === 'image' ? 'image/*' : 'video/*');
                input.onchange = function () {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('file', file);

                    fetch('{{ route('admin.upload.media') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    }).then(response => response.json()).then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
                        } else {
                            alert('Ошибка: сервер не вернул ссылку на файл.');
                        }
                    }).catch(error => {
                        alert('Ошибка загрузки файла: ' + error.message);
                    });
                };
                input.click();
            }
        });
    </script>
@endpush
