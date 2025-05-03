@extends('layouts.admin')

@section('title', 'Создать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Создать новость</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Заголовок --}}
        <div class="mb-4">
            <label for="title" class="block mb-1 font-semibold">Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        {{-- Категории --}}
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Категории</label>
            <div class="flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="mr-2">
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Шаблон --}}
        <div class="mb-4">
            <label for="template" class="block mb-1 font-semibold">Шаблон</label>
            <select name="template" id="template" class="w-full border rounded px-3 py-2">
                <option value="">Новости</option>
                <option value="products" {{ old('template', $news->template ?? '') == 'products' ? 'selected' : '' }}>Товары</option>
                <option value="contacts" {{ old('template', $news->template ?? '') == 'contacts' ? 'selected' : '' }}>Контакты</option>
                <option value="gallery" {{ old('template', $news->template ?? '') == 'gallery' ? 'selected' : '' }}>Галерея</option>
            </select>
        </div>

        {{-- Контент --}}
        <div class="mb-4">
            <label for="content" class="block mb-1 font-semibold">Содержимое</label>
            <textarea name="content" id="editor" rows="12"
                      class="w-full border rounded px-3 py-2">{{ old('content') }}</textarea>
        </div>

        {{-- Публикация --}}
        <div class="mb-4">
            <label class="inline-flex items-center">
                <input type="checkbox" name="published" value="1" class="mr-2" checked>
                Опубликовать
            </label>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Сохранить
        </button>
    </form>

    {{-- TinyMCE --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 500,
            branding: false,
            convert_urls: false,
            license_key: 'gpl',
            automatic_uploads: true,
            plugins: [
                'image', 'media', 'mediaembed', 'link', 'lists', 'table', 'code', 'visualblocks', 'wordcount'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
                'link image media mediaembed table | code | removeformat',
            fontsize_formats: '10px 12px 14px 16px 18px 24px 36px',
            extended_valid_elements: 'iframe[src|frameborder|style|scrolling|class|width|height|name|align|allow|allowfullscreen|sandbox]',
            valid_children: '+body[iframe]',
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
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
                        } else {
                            alert('Ошибка: сервер не вернул ссылку на файл.');
                        }
                    })
                    .catch(error => {
                        alert('Ошибка загрузки файла: ' + error.message);
                    });
                };

                input.click();
            }
        });
    </script>
@endsection
