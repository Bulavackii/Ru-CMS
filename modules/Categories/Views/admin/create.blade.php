@extends('layouts.admin')

@section('title', 'Создать категорию')

@section('content')
    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                @themeIcon('tag') Создать категорию
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Заполните данные категории. Slug будет сгенерирован автоматически, если не указан.
            </p>
        </div>

        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:underline">
            @themeIcon('arrow-left') Назад к списку
        </a>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-300/70 bg-red-50 px-4 py-3 text-red-800 dark:border-red-900/40 dark:bg-red-900/30 dark:text-red-100">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.categories.store') }}"
          class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow p-6 max-w-2xl"
          id="catForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Title --}}
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('label') Название категории <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    value="{{ old('title') }}"
                    maxlength="255"
                    autofocus
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Например: Новости" required>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Рекомендуется до 60 символов.
                </p>
            </div>

            {{-- Slug --}}
            <div class="md:col-span-2">
                <label for="slug" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('link') URL (slug)
                </label>
                <input
                    type="text"
                    name="slug"
                    id="slug"
                    value="{{ old('slug') }}"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Будет сгенерирован автоматически">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Только латинские буквы, цифры и дефисы. Оставьте пустым для автогенерации.
                </p>
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('file-text') Описание
                </label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    maxlength="1000"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Краткое описание категории">{{ old('description') }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Максимум 1000 символов.
                </p>
            </div>

            {{-- Type --}}
            <div>
                <label for="type" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('tag') Тип
                </label>
                <input
                    type="text"
                    name="type"
                    id="type"
                    value="{{ old('type') }}"
                    maxlength="50"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Например: news, product">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Для группировки категорий.
                </p>
            </div>

            {{-- Icon --}}
            <div>
                <label for="icon" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('image') Иконка
                </label>
                <input
                    type="text"
                    name="icon"
                    id="icon"
                    value="{{ old('icon') }}"
                    maxlength="100"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Эмодзи или HTML">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Эмодзи или HTML код иконки.
                </p>
            </div>

            {{-- Parent --}}
            <div>
                <label for="parent_id" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('folder') Родительская категория
                </label>
                <select
                    name="parent_id"
                    id="parent_id"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Корневая категория</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->title }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Для создания иерархии категорий.
                </p>
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    @themeIcon('sort') Порядок сортировки
                </label>
                <input
                    type="number"
                    name="sort_order"
                    id="sort_order"
                    value="{{ old('sort_order', 0) }}"
                    min="0"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Меньшее значение = выше в списке.
                </p>
            </div>

            {{-- Is Active --}}
            <div class="md:col-span-2">
                <label class="inline-flex items-center">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        @themeIcon('check') Активна (категория будет видна на сайте)
                    </span>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center pt-6 border-t border-gray-200 dark:border-gray-700">
            <button type="submit" id="submitBtn"
                    class="inline-flex items-center justify-center gap-2 w-full sm:w-auto bg-black hover:bg-gray-800 text-white px-5 py-2.5 rounded-lg text-sm shadow transition disabled:opacity-50">
                @themeIcon('save') Сохранить
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                @themeIcon('xmark') Отмена
            </a>

            <span class="text-xs text-gray-500 dark:text-gray-400 sm:ml-auto">
                Горячие клавиши: <b>Ctrl + S</b> — сохранить, <b>Esc</b> — назад
            </span>
        </div>
    </form>

    {{-- Styles --}}
    <style>
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
