@extends('layouts.admin')

@section('title', 'Редактировать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Редактировать новость</h1>

    {{-- Ошибки --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 🛠 Исправлено: передаём id через массив --}}
    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}">
        @csrf
        @method('PUT')

        {{-- Заголовок --}}
        <div class="mb-4">
            <label for="title" class="block mb-1 font-semibold">Заголовок</label>
            <input type="text" name="title" id="title"
                   value="{{ old('title', $news->title) }}"
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
            <textarea name="content" id="editor" rows="12"
                      class="w-full border rounded px-3 py-2">{{ old('content', $news->content) }}</textarea>
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

    {{-- Подключение локального TinyMCE --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            branding: false,
            plugins: [
                'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media',
                'searchreplace', 'table', 'visualblocks', 'wordcount',
                'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker',
                'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage',
                'advtemplate', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags',
                'autocorrect', 'typography', 'inlinecss', 'markdown', 'importword', 'exportword', 'exportpdf'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | ' +
                     'link image media table mergetags | addcomment showcomments | spellcheckdialog ' +
                     'a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | ' +
                     'emoticons charmap | removeformat',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
            mergetags_list: [
                { value: 'First.Name', title: 'First Name' },
                { value: 'Email', title: 'Email' },
            ],
        });
    </script>
@endsection
