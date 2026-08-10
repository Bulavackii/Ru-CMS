@extends('layouts.admin')

@section('title', __('admin.categories.create_title'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-tag"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.categories.create_heading') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.categories.create_sub') }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.categories.to_list') }}
        </a>
    </div>

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

    {{-- Форма: во всю ширину, поля в две колонки — так она заметно ниже --}}
    <form method="POST" action="{{ route('admin.categories.store') }}" class="w-full space-y-5" id="catForm">
        @csrf

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
                            value="{{ old('title') }}"
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
                            value="{{ old('slug') }}"
                            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                            class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            placeholder="{{ __('admin.categories.f_slug_ph') }}">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.categories.f_slug_hint') }}
                        </p>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label for="type" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.type') }}</label>
                        <input
                            type="text"
                            name="type"
                            id="type"
                            value="{{ old('type') }}"
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
                            placeholder="{{ __('admin.categories.f_desc_ph') }}">{{ old('description') }}</textarea>
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
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_parent_hint') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Icon --}}
                        <div>
                            <label for="icon" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ __('admin.categories.f_icon') }}</label>
                            <input
                                type="text"
                                name="icon"
                                id="icon"
                                value="{{ old('icon') }}"
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
                                value="{{ old('sort_order', 0) }}"
                                min="0"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.categories.f_order_hint') }}</p>
                        </div>
                    </div>

                    {{-- Тумблер вместо голой галочки: тот же элемент, что в
                         «Меню» и «Слайдшоу». Галочка рядом с ними читалась
                         как другой элемент управления, хотя делает ровно то
                         же. Имя поля и значение не менялись. --}}
                    <div class="pt-1 border-t border-gray-100 dark:border-gray-800">
                        <label class="cat-switch">
                            <span class="admin-toggle">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span class="track"></span><span class="knob"></span>
                            </span>
                            <span class="cat-switch__body">
                                <span class="cat-switch__title">{{ __('admin.categories.active') }}</span>
                                <span class="cat-switch__note">{{ __('admin.categories.f_active_hint') }}</span>
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
                {{ __('admin.categories.hotkeys') }} <b>Ctrl + S</b> {{ __('admin.categories.hotkeys_save') }} <b>Esc</b> {{ __('admin.categories.hotkeys_back') }}
            </span>
        </div>
    </form>

    {{-- Styles --}}
    <style>
        /* Строка-тумблер: подпись и пояснение рядом с рычажком, а не под
           галочкой. Литеральный CSS — в сборке проекта нет ни прозрачности
           через дробь, ни варианта peer-checked. */
        .cat-switch{ display:inline-flex; align-items:flex-start; gap:.6rem; margin-top:1rem; cursor:pointer }
        .cat-switch__body{ display:flex; flex-direction:column; gap:.15rem; line-height:1.35 }
        .cat-switch__title{ font-size:.875rem; font-weight:600; color:#374151 }
        .cat-switch__note{ font-size:.75rem; color:#6b7280 }
        .dark .cat-switch__title{ color:#e5e7eb }
        .dark .cat-switch__note{ color:#9ca3af }

        @keyframes fade-in { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        #catForm { animation: fade-in .25s ease-out }
    </style>

    {{-- Scripts --}}
    <script>
        (function () {
            const form = document.getElementById('catForm');
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const submitBtn = document.getElementById('submitBtn');

            // Автогенерация slug из title
            let manualSlug = false;
            slugInput.addEventListener('input', () => {
                manualSlug = slugInput.value.length > 0;
            });

            titleInput.addEventListener('input', () => {
                if (!manualSlug) {
                    const slug = titleInput.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                }
            });

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
        })();
    </script>
@endsection
