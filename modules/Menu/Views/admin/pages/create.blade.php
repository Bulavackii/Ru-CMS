@extends('layouts.admin')

@section('title', 'Создать страницу')

@section('content')
    {{-- 📝 Заголовок и подзаголовок --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📝 Создание страницы</h1>
        <span class="text-sm text-gray-500 dark:text-gray-400">📄 Новая статическая или контентная страница</span>
    </div>

    {{-- ⚠️ Ошибки валидации --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow animate-pulse">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    {{-- 🧾 Форма создания страницы --}}
    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6">
        @csrf

        {{-- 📄 Заголовок страницы --}}
        <x-admin.input label="📄 Заголовок" name="title" :value="old('title')" required
            hint="Основной заголовок страницы, отображается в интерфейсе и в заголовке браузера." />

        {{-- 🧠 SEO-информация --}}
        <x-admin.input label="🔖 Meta Title" name="meta_title" :value="old('meta_title')"
            hint="Отображается в поисковой выдаче. До 60 символов. Можно использовать «|» или «—» для разделения." />

        <x-admin.input label="📝 Meta Description" name="meta_description" :value="old('meta_description')"
            hint="Краткое описание страницы до 160 символов. Увеличивает CTR в поисковиках." />

        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" :value="old('meta_keywords')"
            hint="Слова через запятую: вода, экология, природа. Используются поисковыми системами." />

        {{-- 🔗 Slug (ссылка на страницу) --}}
        <x-admin.input label="🔗 Slug (ссылка)" name="slug" :value="old('slug')"
            hint="URL-адрес страницы. Оставьте пустым — сгенерируется автоматически." />

        {{-- 📂 Категории страницы --}}
        <div>
            <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">📂 Категории</label>
            <p class="text-sm text-gray-500 mb-2">Выберите одну или несколько категорий, к которым относится страница.</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label
                        class="flex items-center px-3 py-1 border border-gray-300 rounded-full cursor-pointer text-sm hover:bg-blue-50 dark:border-gray-600 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            class="form-checkbox text-blue-600 mr-2"
                            {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 📝 Контент страницы --}}
        <div>
            <label for="editor" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Контент</label>
            <textarea name="content" id="editor" rows="12"
                class="w-full border border-gray-300 rounded px-3 py-2 dark:bg-gray-800 dark:text-white"
                placeholder="Введите основной текст страницы, можно добавлять изображения, видео и форматированный текст.">{{ old('content') }}</textarea>
        </div>

        {{-- ⚙️ Настройки публикации и кнопка --}}
        <div class="pt-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                {{-- ✅ Опубликовать --}}
                <label class="inline-flex items-center text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="published" value="1" class="mr-2"
                        {{ is_null(old('published')) ? 'checked' : (old('published') ? 'checked' : '') }}>
                     Опубликовать страницу
                </label>

                {{-- 🏠 Показывать на главной --}}
                <label class="inline-flex items-center text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="show_on_homepage" value="1" class="mr-2"
                        {{ old('show_on_homepage') ? 'checked' : '' }}>
                     Показать на главной странице
                </label>

                {{-- 🔢 Порядок на главной --}}
                <x-admin.input label="🔢 Порядок" name="homepage_order" type="number"
                    :value="old('homepage_order', 0)" class="w-32"
                    hint="Чем меньше число — тем выше в списке на главной." />
            </div>

            {{-- 💾 Кнопка сохранения --}}
            <div class="text-right">
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow text-sm">
                    💾 Сохранить страницу
                </button>
            </div>
        </div>
    </form>

    {{-- 🧠 TinyMCE редактор --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 600,
            branding: false,
            license_key: 'gpl',
            convert_urls: false,
            plugins: 'image media link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code | removeformat',
            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = meta.filetype === 'image' ? 'image/*' : 'video/*';
                input.onchange = function() {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('file', file);
                    fetch('{{ route('admin.upload.media') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.location) {
                            callback(data.location, {
                                title: file.name
                            });
                        } else {
                            alert('Ошибка загрузки.');
                        }
                    })
                    .catch(error => alert('Ошибка: ' + error.message));
                };
                input.click();
            }
        });
    </script>
@endsection
