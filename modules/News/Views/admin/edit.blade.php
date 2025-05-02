@extends('layouts.admin')

@section('title', 'Редактировать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Редактировать новость</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Заголовок --}}
        <div class="mb-4">
            <label for="title" class="block mb-1 font-semibold">Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title', $news->title) }}"
                class="w-full border rounded px-3 py-2" required>
        </div>

        {{-- Категории --}}
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Категории</label>
            <div class="flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            {{ $news->categories->contains($category->id) ? 'checked' : '' }} class="mr-2">
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Контент --}}
        <div class="mb-4">
            <label for="content" class="block mb-1 font-semibold">Содержимое</label>
            <textarea name="content" id="editor" rows="12" class="w-full border rounded px-3 py-2">{{ old('content', $news->content) }}</textarea>
        </div>

        {{-- Публикация --}}
        <div class="mb-4">
            <label class="inline-flex items-center">
                <input type="checkbox" name="published" value="1" class="mr-2"
                    {{ $news->published ? 'checked' : '' }}>
                Опубликовать
            </label>
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Сохранить изменения
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
            plugins: [
                'image', 'media', 'link', 'lists', 'table', 'code', 'visualblocks', 'wordcount'
            ],
            toolbar: 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright | ' +
                'bullist numlist outdent indent | link image media table | code | removeformat',

            convert_urls: false,
            automatic_uploads: true,
            images_upload_url: '{{ route('admin.upload.media') }}',
            media_upload_url: '{{ route('admin.upload.media') }}',
            images_upload_credentials: true,

            file_picker_types: 'image media',
            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', meta.filetype === 'image' ? 'image/*' : 'video/*');

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
                        .then(data => callback(data.location, {
                            title: file.name
                        }))
                        .catch(() => alert('Ошибка загрузки файла'));
                };

                input.click();
            }
        });
    </script>
@endsection
