@extends('layouts.admin')

@section('title', 'Создание уведомления')

@section('content')
    {{-- 📝 Заголовок страницы --}}
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
        📝 Создать уведомление
    </h1>

    {{-- ⚠️ Ошибка валидации --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-4 py-3 rounded mb-6 shadow">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    {{-- 📋 Форма создания --}}
    <form method="POST" action="{{ route('admin.notifications.store') }}"
          class="space-y-6 w-full bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-800">
        @csrf

        {{-- 🧾 Заголовок --}}
        <x-admin.input label="📌 Заголовок" name="title" required />

        {{-- 📋 Тип уведомления --}}
        <x-admin.select label="📂 Тип уведомления" name="type" :options="[
            'text' => 'Текст',
            'cookie' => 'Cookie',
        ]" selected="text" />

        {{-- 👥 Аудитория --}}
        <x-admin.select label="🎯 Показать для" name="target" :options="[
            'all' => 'Все',
            'admin' => 'Только админы',
            'user' => 'Только пользователи',
        ]" selected="all" />

        {{-- 📍 Позиция --}}
        <x-admin.select label="📍 Позиция на экране" name="position" :options="[
            'top' => 'Сверху',
            'bottom' => 'Снизу',
            'fullscreen' => 'Во весь экран',
        ]" selected="top" />

        {{-- 🖼️ Иконка --}}
        <x-admin.input label="🔔 Иконка (emoji или FontAwesome)" name="icon" value="🔔"
                       hint="Например: 🔔, ⚠️, ✅ или fa-solid fa-info" />

        {{-- 🎨 Цвета --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-admin.input label="🎨 Цвет фона (HEX)" name="bg_color" value="#E6F3F9" />
            <x-admin.input label="🖋️ Цвет текста (HEX)" name="text_color" value="#000000" />
        </div>

        {{-- 💬 Сообщение --}}
        <div>
            <label for="editor" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
                📝 Содержимое
            </label>
            <textarea name="message" id="editor" rows="6"
                      class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100">{{ old('message') }}</textarea>
        </div>

        {{-- ⏱️ Время показа --}}
        <x-admin.input label="⏱️ Время показа (в секундах)" name="duration" type="number" value="0"
                       hint="0 = пока пользователь не закроет вручную" />

        {{-- 🧭 Фильтр маршрута --}}
        <x-admin.input label="🗺️ Фильтр маршрута (URL)" name="route_filter" value="/"
                       hint="Примеры: /, /news/*, /profile" />

        {{-- 🍪 Ключ cookie --}}
        <x-admin.input label="🍪 Ключ cookie (опционально)" name="cookie_key"
                       hint="Уникальный ключ, если хотите управлять показом через cookie" />

        {{-- ✅ Кнопка сохранения --}}
        <div class="pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                💾 Сохранить
            </button>
        </div>
    </form>
@endsection

{{-- 📜 TinyMCE редактор --}}
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
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media mediaembed table | code | removeformat',
            file_picker_types: 'image media',
            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = meta.filetype === 'image' ? 'image/*' : 'video/*';
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
                    }).then(res => res.json()).then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
                        } else {
                            alert('Ошибка: сервер не вернул ссылку на файл.');
                        }
                    }).catch(error => {
                        alert('Ошибка загрузки: ' + error.message);
                    });
                };
                input.click();
            }
        });
    </script>
@endpush
