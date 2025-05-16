@extends('layouts.admin')

@section('title', 'Создать страницу')

@section('content')
    <h1 class="text-2xl font-bold mb-6">📝 Создание страницы</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow animate-pulse">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6">
        @csrf

        {{-- Заголовок --}}
        <x-admin.input label="📄 Заголовок" name="title" :value="old('title')" required />

        {{-- SEO --}}
        <x-admin.input label="🔖 Meta Title" name="meta_title" :value="old('meta_title')" />
        <x-admin.input label="📄 Meta Description" name="meta_description" :value="old('meta_description')" />
        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" :value="old('meta_keywords')" />

        {{-- Slug --}}
        <x-admin.input label="🔗 Slug (ссылка)" name="slug" :value="old('slug')" />

        {{-- Категории --}}
        <div>
            <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">📂 Категории</label>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label
                        class="flex items-center px-3 py-1 border border-gray-300 rounded-full cursor-pointer text-sm hover:bg-blue-50 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            class="form-checkbox text-blue-600 mr-2"
                            {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Контент --}}
        <div>
            <label for="editor" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Контент</label>
            <textarea name="content" id="editor" rows="12"
                class="w-full border border-gray-300 rounded px-3 py-2 dark:bg-gray-800 dark:text-white">{{ old('content') }}</textarea>
        </div>

        {{-- Настройки публикации --}}
        <div class="flex flex-col sm:flex-row gap-6">
            <label class="inline-flex items-center">
                <input type="checkbox" name="published" value="1" class="mr-2"
                    {{ is_null(old('published')) ? 'checked' : (old('published') ? 'checked' : '') }}>
                ✅ Опубликовать
            </label>

            <label class="inline-flex items-center">
                <input type="checkbox" name="show_on_homepage" value="1" class="mr-2"
                    {{ old('show_on_homepage') ? 'checked' : '' }}>
                🏠 Показать на главной
            </label>

            <x-admin.input label="🔢 Порядок на главной" name="homepage_order" type="number" :value="old('homepage_order', 0)"
                class="w-32" />
        </div>

        {{-- Кнопка --}}
        <div class="pt-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
                💾 Сохранить страницу
            </button>
        </div>
    </form>

    {{-- TinyMCE --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 600,
            branding: false,
            convert_urls: false,
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media mediaembed table | code | removeformat',
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
