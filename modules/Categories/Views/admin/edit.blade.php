@extends('layouts.admin')

@section('title', __('admin.categories.edit_title'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-tag"></i></span>
            <div class="min-w-0 space-y-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $category->title }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if ($category->is_active)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                            <i class="fas fa-circle-check"></i> {{ __('admin.categories.active') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                            <i class="fas fa-ban"></i> {{ __('admin.categories.inactive') }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        ID {{ $category->id }}
                    </span>
                    @if ($category->slug)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                            /{{ $category->slug }}
                        </span>
                    @endif
                    @if ($category->type)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $category->type }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.categories.to_list') }}
        </a>
    </div>

    {{-- Использование категории --}}
    @if(isset($usageCounts) && ($usageCounts['news'] > 0 || $usageCounts['pages'] > 0 || $usageCounts['children'] > 0))
        <div class="admin-note px-4 py-3 mb-5 text-sm">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-medium flex items-center gap-2"><i class="fas fa-chart-simple"></i> {{ __('admin.categories.usage') }}</span>
                @if($usageCounts['news'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-newspaper text-indigo-500"></i> {{ __('admin.categories.usage_news') }} <b>{{ $usageCounts['news'] }}</b>
                    </span>
                @endif
                @if($usageCounts['pages'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-file-lines text-indigo-500"></i> {{ __('admin.categories.usage_pages') }} <b>{{ $usageCounts['pages'] }}</b>
                    </span>
                @endif
                @if($usageCounts['children'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-folder-tree text-indigo-500"></i> {{ __('admin.categories.usage_children') }} <b>{{ $usageCounts['children'] }}</b>
                    </span>
                @endif
            </div>
        </div>
    @endif

    {{-- Ошибки валидации --}}
    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form
        id="catEditForm"
        method="POST"
        action="{{ route('admin.categories.update', ['id' => $category->id]) }}"
        class="w-full space-y-5">

        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

            {{-- ── Левая колонка: основное ── --}}
            <div class="admin-card p-5 lg:col-span-2">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-tag text-indigo-500"></i> {{ __('admin.categories.g_main') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Title --}}
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.categories.f_title') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            value="{{ old('title', $category->title) }}"
                            maxlength="255"
                            autofocus
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            placeholder="{{ __('admin.categories.f_title_ph') }}" required>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_title_hint') }}</p>
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">URL (slug)</label>
                        <input
                            type="text"
                            name="slug"
                            id="slug"
                            value="{{ old('slug', $category->slug) }}"
                            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            placeholder="{{ __('admin.categories.f_slug_ph') }}">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.categories.f_slug_hint_edit') }}
                        </p>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label for="type" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.type') }}</label>
                        <input
                            type="text"
                            name="type"
                            id="type"
                            value="{{ old('type', $category->type) }}"
                            maxlength="50"
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            placeholder="{{ __('admin.categories.f_type_ph') }}">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_type_hint') }}</p>
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.f_desc') }}</label>
                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            maxlength="1000"
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            placeholder="{{ __('admin.categories.f_desc_ph') }}">{{ old('description', $category->description) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_desc_hint') }}</p>
                    </div>
                </div>
            </div>

            {{-- ── Правая колонка: параметры ── --}}
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-sliders text-indigo-500"></i> {{ __('admin.categories.g_params') }}
                </h2>

                <div class="space-y-5">
                    {{-- Parent --}}
                    <div>
                        <label for="parent_id" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.f_parent') }}</label>
                        <select
                            name="parent_id"
                            id="parent_id"
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">{{ __('admin.categories.f_parent_root') }}</option>
                            @foreach($parentCategories ?? [] as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.categories.f_parent_hint_edit') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Icon --}}
                        <div>
                            <label for="icon" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.f_icon') }}</label>
                            <input
                                type="text"
                                name="icon"
                                id="icon"
                                value="{{ old('icon', $category->icon) }}"
                                maxlength="100"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="{{ __('admin.categories.f_icon_ph') }}">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_icon_hint') }}</p>
                        </div>

                        {{-- Sort Order --}}
                        <div>
                            <label for="sort_order" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.f_order') }}</label>
                            <input
                                type="number"
                                name="sort_order"
                                id="sort_order"
                                value="{{ old('sort_order', $category->sort_order) }}"
                                min="0"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_order_hint') }}</p>
                        </div>
                    </div>

                    {{-- Is Active --}}
                    <div class="pt-1 border-t border-gray-100 dark:border-gray-800">
                        <label class="inline-flex items-start gap-2 mt-4 cursor-pointer">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                class="mt-0.5">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ __('admin.categories.active') }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_active_hint') }}</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="admin-card p-5 flex flex-col sm:flex-row gap-3 sm:items-center">
            <button type="submit" id="submitBtn"
                    class="inline-flex items-center justify-center gap-2 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition disabled:opacity-50">
                <i class="fas fa-floppy-disk"></i> {{ __('admin.categories.save') }}
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                {{ __('admin.categories.cancel') }}
            </a>

            <span class="text-xs text-gray-500 dark:text-gray-400 sm:ml-auto">
                {{ __('admin.categories.hotkeys') }} <b>Ctrl/Cmd + S</b> {{ __('admin.categories.hotkeys_save') }} <b>Esc</b> {{ __('admin.categories.hotkeys_back') }}
            </span>
        </div>
    
    {{-- Переводы контента на другие языки (content_translations) --}}
    <x-admin.translations :model="$category" :fields="['title' => __('admin.categories.th_name'), 'description' => ['label' => __('admin.categories.f_desc'), 'type' => 'textarea']]" />

</form>

    {{-- Styles --}}
    <style>
        @keyframes fade-in { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        #catEditForm { animation: fade-in .25s ease-out }
    </style>

    {{-- Scripts --}}
    <script>
        (function () {
            const form = document.getElementById('catEditForm');
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const submitBtn = document.getElementById('submitBtn');

            const initialValues = {
                title: titleInput.value,
                slug: slugInput.value,
            };

            // Автогенерация slug из title (если slug пустой)
            let manualSlug = slugInput.value.length > 0;
            slugInput.addEventListener('input', () => {
                manualSlug = slugInput.value.length > 0;
            });

            titleInput.addEventListener('input', () => {
                if (!manualSlug && !slugInput.value) {
                    const slug = titleInput.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                }
            });

            // Проверка изменений
            function checkChanges() {
                const changed = titleInput.value !== initialValues.title || 
                               slugInput.value !== initialValues.slug;
                submitBtn.disabled = !changed && titleInput.value.trim().length === 0;
            }

            titleInput.addEventListener('input', checkChanges);
            slugInput.addEventListener('input', checkChanges);
            checkChanges();

            // Ctrl/Cmd + S => submit
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    if (!submitBtn.disabled) form.submit();
                }
                if (e.key === 'Escape') {
                    window.location.href = @json(route('admin.categories.index'));
                }
            });

            // Warn on unsaved changes
            let formChanged = false;
            form.addEventListener('change', () => formChanged = true);
            form.addEventListener('input', () => formChanged = true);

            window.addEventListener('beforeunload', function (e) {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            form.addEventListener('submit', () => {
                formChanged = false;
            });
        })();
    </script>
@endsection
