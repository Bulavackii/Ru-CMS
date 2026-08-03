@extends('layouts.admin')

@section('title', __('admin.pages.page_create'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fa-solid fa-file-circle-plus"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.pages.create') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pages.create_hint') }}</p>
            </div>
        </div>
        <a href="{{ route('admin.pages.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 shrink-0">
            <i class="fa-solid fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
        </a>
    </div>

    {{-- ⚠️ Ошибки валидации --}}
    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-100 px-4 py-3 mb-6">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    {{-- Форма создания страницы --}}
    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-5">
        @csrf
        {{-- Маркер отправки: отличаем первую загрузку (галочка «Опубликовать»
             по умолчанию стоит) от возврата после ошибки валидации со снятой
             галочкой. Без него old('published') == null на первой загрузке и
             после снятия галочки неразличимы, и снять публикацию было нельзя. --}}
        <input type="hidden" name="_submitted" value="1">

        {{-- ── Основное ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-indigo-500"></i> {{ __('admin.common.basic') }}
            </h2>
            <div class="space-y-4">
                <x-admin.input label="{{ __('admin.common.f_title') }}" name="title" :value="old('title')" required
                    hint="{{ __('admin.pages.title_hint') }}" />
                <x-admin.input label="{{ __('admin.common.f_slug') }}" name="slug" :value="old('slug')"
                    hint="{{ __('admin.pages.slug_hint') }}" />
            </div>
        </div>

        {{-- ── SEO ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass text-indigo-500"></i> SEO
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Meta Title" name="meta_title" :value="old('meta_title')"
                    hint="{{ __('admin.pages.meta_title_hint') }}" />
                <x-admin.input label="Meta Description" name="meta_description" :value="old('meta_description')"
                    hint="{{ __('admin.pages.meta_desc_hint') }}" />
                <x-admin.input label="{{ __('admin.common.f_keywords') }}" name="meta_keywords" :value="old('meta_keywords')"
                    hint="{{ __('admin.pages.keywords_hint') }}" />
            </div>
        </div>

        {{-- ── Категории ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-indigo-500"></i> {{ __('admin.common.categories') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('admin.pages.categories_hint') }}</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($categories as $category)
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm
                                  hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('admin.common.no_categories') }}</p>
                @endforelse
            </div>
        </div>

        {{-- ── Контент ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-indigo-500"></i> {{ __('admin.common.content') }}
            </h2>
            <textarea name="content" id="editor" rows="12"
                class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-white"
                placeholder="{{ __('admin.pages.content_hint') }}">{{ old('content') }}</textarea>
        
            {{-- Вставка сохранённой сборки каптчи в текст материала --}}
            @include('Captcha::partials.editor-picker')
</div>

        {{-- ── Публикация и сохранение ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-indigo-500"></i> {{ __('admin.common.publication') }}
            </h2>

            <div class="flex flex-col lg:flex-row lg:items-end gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="published" value="1"
                            {{ old('_submitted') ? (old('published') ? 'checked' : '') : 'checked' }}>
                        {{ __('admin.pages.publish') }}
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="show_on_homepage" value="1"
                            {{ old('show_on_homepage') ? 'checked' : '' }}>
                        {{ __('admin.pages.show_on_home') }}
                    </label>

                    <x-admin.input label="{{ __('admin.pages.home_order') }}" name="homepage_order" type="number"
                        :value="old('homepage_order', 0)" class="w-32"
                        hint="{{ __('admin.pages.home_order_hint') }}" />
                </div>

                <div class="lg:ml-auto flex items-center gap-2">
                    <a href="{{ route('admin.pages.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                              text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{ __('admin.cancel') }}
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 shadow-sm text-sm font-semibold transition">
                        <i class="fa-solid fa-floppy-disk"></i> {{ __('admin.pages.save') }}
                    </button>
                </div>
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
                            alert('{{ __('admin.common.load_error') }}');
                        }
                    })
                    .catch(error => alert('Ошибка: ' + error.message));
                };
                input.click();
            }
        });
    </script>
@endsection
