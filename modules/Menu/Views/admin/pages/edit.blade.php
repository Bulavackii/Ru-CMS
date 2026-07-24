@extends('layouts.admin')

@section('title', 'Редактировать страницу')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fa-solid fa-pen-to-square"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">Редактировать страницу</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $page->title }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 shrink-0">
            <a href="{{ route('frontend.pages.show', $page->slug) }}" target="_blank"
               class="hidden sm:inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400"
               title="Открыть страницу на сайте">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> На сайте
            </a>
            <a href="{{ route('admin.pages.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fa-solid fa-arrow-left"></i> К списку
            </a>
        </div>
    </div>

    {{-- ⚠️ Сообщение об ошибке валидации --}}
    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-100 px-4 py-3 mb-6">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    {{-- 🧾 Форма редактирования страницы --}}
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 📄 Заголовок страницы --}}
        <x-admin.input label="📄 Заголовок" name="title" :value="old('title', $page->title)" required
            hint="Основной заголовок страницы. Отображается в интерфейсе и в заголовке браузера." />

        {{-- 🧠 SEO-блок --}}
        <x-admin.input label="🔖 Meta Title" name="meta_title" :value="old('meta_title', $page->meta_title)"
            hint="Заголовок для поисковых систем. До 60 символов. Используйте «|» или «—» для отделения ключевых слов." />

        <x-admin.input label="📝 Meta Description" name="meta_description" :value="old('meta_description', $page->meta_description)"
            hint="Описание страницы до 160 символов. Важно для CTR в поисковой выдаче." />

        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" :value="old('meta_keywords', $page->meta_keywords)"
            hint="Ключевые слова через запятую: экология, вода, ресурсы. Учитываются поисковиками." />

        {{-- 🔗 Slug (ссылка) --}}
        <x-admin.input label="🔗 Slug (ссылка)" name="slug" :value="old('slug', $page->slug)"
            hint="Пользовательская часть URL. Допустимы только латиница, тире и цифры. Пример: o-nas или contact" />

        {{-- 📂 Категории --}}
        <div>
            <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">📂 Категории</label>
            <p class="text-sm text-gray-500 mb-2">Выберите категории, к которым относится эта страница. Можно выбрать несколько.</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm hover:bg-indigo-50 hover:border-indigo-400 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               class="form-checkbox text-indigo-600 mr-2"
                               {{ in_array($category->id, old('categories', $page->categories->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                      placeholder="Введите или отредактируйте содержимое страницы. Поддерживается форматирование, вставка изображений и видео.">{{ old('content', $page->content) }}</textarea>
        </div>

        {{-- ⚙️ Настройки публикации и кнопка --}}
        <div class="pt-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                {{-- ✅ Опубликовать --}}
                <label class="inline-flex items-center text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="published" value="1" class="mr-2"
                        {{ old('published', $page->published) ? 'checked' : '' }}>
                    ✅ Опубликовать страницу
                </label>

                {{-- 🏠 Показывать на главной --}}
                <label class="inline-flex items-center text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="show_on_homepage" value="1" class="mr-2"
                        {{ old('show_on_homepage', $page->show_on_homepage) ? 'checked' : '' }}>
                    🏠 Показать на главной странице
                </label>

                {{-- 🔢 Порядок на главной --}}
                <x-admin.input label="🔢 Порядок" name="homepage_order" type="number"
                    :value="old('homepage_order', $page->homepage_order)" class="w-32"
                    hint="Чем меньше значение, тем выше блок на главной странице." />
            </div>

            {{-- 💾 Кнопка сохранения --}}
            <div class="text-right">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 shadow-sm text-sm font-semibold transition">
                    <i class="fa-solid fa-floppy-disk"></i> Сохранить изменения
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
                input.onchange = function () {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('file', file);
                    fetch('{{ route('admin.upload.media') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
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
