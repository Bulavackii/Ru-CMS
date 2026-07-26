@extends('layouts.admin')

@section('title', 'Редактировать страницу')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fa-solid fa-pen-to-square"></i></span>
            <div class="min-w-0 space-y-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $page->title }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if ($page->published)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                            <i class="fa-solid fa-circle-check"></i> Опубликовано
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                            <i class="fa-solid fa-clock"></i> Черновик
                        </span>
                    @endif
                    @if ($page->show_on_homepage)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                            <i class="fa-solid fa-house"></i> На главной
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        ID {{ $page->id }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                        /{{ $page->slug }}
                    </span>
                </div>
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

    {{-- Форма редактирования страницы --}}
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- ── Основное ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-indigo-500"></i> Основное
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Заголовок" name="title" :value="old('title', $page->title)" required
                    hint="Основной заголовок страницы — в интерфейсе и в заголовке браузера." />
                <x-admin.input label="Slug (ссылка)" name="slug" :value="old('slug', $page->slug)"
                    hint="Часть URL. Допустимы латиница, цифры и дефисы. Пример: o-nas или contact." />
            </div>
        </div>

        {{-- ── SEO ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass text-indigo-500"></i> SEO
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Meta Title" name="meta_title" :value="old('meta_title', $page->meta_title)"
                    hint="Заголовок для поисковых систем. До 60 символов." />
                <x-admin.input label="Meta Description" name="meta_description" :value="old('meta_description', $page->meta_description)"
                    hint="Описание до 160 символов. Важно для CTR в поисковой выдаче." />
                <x-admin.input label="Ключевые слова" name="meta_keywords" :value="old('meta_keywords', $page->meta_keywords)"
                    hint="Через запятую: экология, вода, ресурсы." />
            </div>
        </div>

        {{-- ── Категории ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-indigo-500"></i> Категории
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Выберите категории, к которым относится эта страница.</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($categories as $category)
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm
                                  hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               {{ in_array($category->id, old('categories', $page->categories->pluck('id')->toArray())) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">Категорий пока нет.</p>
                @endforelse
            </div>
        </div>

        {{-- ── Контент ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-indigo-500"></i> Контент
            </h2>
            <textarea name="content" id="editor" rows="12"
                      class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-white"
                      placeholder="Содержимое страницы: форматирование, изображения и видео.">{{ old('content', $page->content) }}</textarea>
        
            {{-- Вставка сохранённой сборки каптчи в текст материала --}}
            @include('Captcha::partials.editor-picker')
</div>

        {{-- ── Публикация и сохранение ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-indigo-500"></i> Публикация
            </h2>

            <div class="flex flex-col lg:flex-row lg:items-end gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="published" value="1"
                            {{ old('published', $page->published) ? 'checked' : '' }}>
                        Опубликовать страницу
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="show_on_homepage" value="1"
                            {{ old('show_on_homepage', $page->show_on_homepage) ? 'checked' : '' }}>
                        Показать на главной
                    </label>

                    <x-admin.input label="Порядок на главной" name="homepage_order" type="number"
                        :value="old('homepage_order', $page->homepage_order)" class="w-32"
                        hint="Чем меньше значение — тем выше блок." />
                </div>

                <div class="lg:ml-auto flex items-center gap-2">
                    <a href="{{ route('admin.pages.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                              text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Отмена
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 shadow-sm text-sm font-semibold transition">
                        <i class="fa-solid fa-floppy-disk"></i> Сохранить изменения
                    </button>
                </div>
            </div>
        </div>
    
        {{-- Переводы контента на другие языки (таблица content_translations) --}}
        <x-admin.translations :model="$page" :fields="['title' => 'Заголовок', 'content' => ['label' => 'Содержимое', 'type' => 'textarea'], 'meta_title' => 'SEO: title', 'meta_description' => ['label' => 'SEO: description', 'type' => 'textarea']]" />

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
