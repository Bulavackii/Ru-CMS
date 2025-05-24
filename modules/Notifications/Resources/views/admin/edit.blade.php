@extends('layouts.admin')

@section('title', 'Редактировать уведомление')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
        ✏️ Редактировать уведомление
    </h1>

    @if ($errors?->any())
        <div class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 p-3 rounded mb-4 shadow">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.update', $notification->id) }}"
          class="space-y-6 w-full bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-800">
        @csrf
        @method('PUT')

        {{-- 📌 Заголовок --}}
        <x-admin.input
            label="📌 Заголовок"
            name="title"
            :value="old('title', $notification->title)"
            required
            placeholder="Например: Уведомление о техработах" />

        {{-- 📋 Тип уведомления --}}
        <x-admin.select
            label="📂 Тип уведомления"
            name="type"
            :options="[
                'text' => 'Текст (отображается всегда)',
                'cookie' => 'Cookie (один раз до закрытия)',
            ]"
            :selected="old('type', $notification->type)"
            placeholder="Выберите тип уведомления" />

        {{-- 👥 Аудитория --}}
        <x-admin.select
            label="🎯 Показать для"
            name="target"
            :options="[
                'all' => 'Все пользователи',
                'admin' => 'Только админы',
                'user' => 'Только авторизованные пользователи',
            ]"
            :selected="old('target', $notification->target)"
            placeholder="Кому показывать" />

        {{-- 📍 Позиция --}}
        <x-admin.select
            label="📍 Позиция на экране"
            name="position"
            :options="[
                'top' => 'Сверху',
                'bottom' => 'Снизу',
                'fullscreen' => 'Во весь экран',
            ]"
            :selected="old('position', $notification->position)"
            placeholder="Выберите позицию" />

        {{-- 🖼️ Иконка --}}
        <x-admin.input
            label="🔔 Иконка (emoji или FontAwesome)"
            name="icon"
            :value="old('icon', $notification->icon)"
            placeholder="Примеры: 🔔, ⚠️, fa-solid fa-info"
            hint="Можно использовать emoji или класс FontAwesome" />

        {{-- 🎨 Цвета --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-admin.input
                label="🎨 Цвет фона (HEX)"
                name="bg_color"
                :value="old('bg_color', $notification->bg_color)"
                placeholder="#E6F3F9" />
            <x-admin.input
                label="🖋️ Цвет текста (HEX)"
                name="text_color"
                :value="old('text_color', $notification->text_color)"
                placeholder="#000000" />
        </div>

        {{-- 💬 Содержимое --}}
        <div>
            <label for="editor" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">📝 Содержимое</label>
            <textarea name="message" id="editor" rows="6"
                      class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100"
                      placeholder="Введите основной текст уведомления...">{{ old('message', $notification->message) }}</textarea>
        </div>

        {{-- ⏱️ Время показа --}}
        <x-admin.input
            label="⏱️ Время показа (в секундах)"
            name="duration"
            type="number"
            :value="old('duration', $notification->duration)"
            placeholder="0 или любое число"
            hint="0 = пока пользователь не закроет" />

        {{-- 🗺️ Фильтр маршрута --}}
        <x-admin.input
            label="🗺️ Маршрут или URL"
            name="route_filter"
            :value="old('route_filter', $notification->route_filter)"
            placeholder="/faq, /about, /news/*"
            hint="Относительный путь, без домена" />

        {{-- 🍪 Ключ cookie --}}
        <x-admin.input
            label="🍪 Ключ cookie (опционально)"
            name="cookie_key"
            :value="old('cookie_key', $notification->cookie_key)"
            placeholder="Уникальный ID, например: welcome_notice" />

        {{-- 💾 Кнопка --}}
        <div class="pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                💾 Обновить
            </button>
        </div>
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
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | ' +
                     'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
                     'link image media mediaembed table | code | removeformat',
            file_picker_types: 'image media',
            file_picker_callback: function (callback, value, meta) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = meta.filetype === 'image' ? 'image/*' : 'video/*';
                input.onchange = function () {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('file', file);
                    fetch('{{ route('admin.upload.media') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    }).then(res => res.json()).then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
                        } else {
                            alert('Ошибка загрузки.');
                        }
                    }).catch(error => {
                        alert('Ошибка: ' + error.message);
                    });
                };
                input.click();
            }
        });
    </script>
@endpush
