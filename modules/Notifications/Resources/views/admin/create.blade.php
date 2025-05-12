@extends('layouts.admin')

@section('title', 'Создание уведомления')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📝 Создать уведомление</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-4 py-3 rounded mb-6 shadow">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.store') }}"
          class="space-y-6 w-full bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-800">
        @csrf

        {{-- Заголовок --}}
        <x-admin.input label="Заголовок" name="title" required />

        {{-- Тип --}}
        <x-admin.select label="Тип уведомления" name="type" :options="[
            'text' => 'Текст',
            'cookie' => 'Cookie',
        ]" selected="text" />

        {{-- Аудитория --}}
        <x-admin.select label="Показать для" name="target" :options="[
            'all' => 'Все',
            'admin' => 'Только админы',
            'user' => 'Только пользователи',
        ]" selected="all" />

        {{-- Позиция --}}
        <x-admin.select label="Позиция" name="position" :options="[
            'top' => 'Сверху',
            'bottom' => 'Снизу',
            'fullscreen' => 'Во весь экран',
        ]" selected="top" />

        {{-- Иконка --}}
        <x-admin.input label="Иконка" name="icon" value="ℹ️" />

        {{-- Цвет фона --}}
        <x-admin.input label="Цвет фона (HEX)" name="bg_color" value="#cccaca" />

        {{-- Цвет текста --}}
        <x-admin.input label="Цвет текста (HEX)" name="text_color" value="#000000" />

        {{-- Содержимое --}}
        <div>
            <label for="editor" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Содержимое</label>
            <textarea name="message" id="editor" rows="6"
                      class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100">{{ old('message') }}</textarea>
        </div>

        {{-- Время показа --}}
        <x-admin.input label="⏰ Время показа (сек)" name="duration" type="number" value="0"
                       hint="0 = пока не закроет пользователь" />

        {{-- Фильтр маршрута --}}
        <x-admin.input label="Маршрут или URL" name="route_filter" value="/" />

        {{-- Ключ cookie --}}
        <x-admin.input label="Ключ cookie (если нужно)" name="cookie_key" />

        {{-- Кнопка --}}
        <div class="pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                💾 Сохранить
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
