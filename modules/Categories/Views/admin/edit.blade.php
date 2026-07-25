@extends('layouts.admin')

@section('title', 'Редактировать категорию')

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
                            <i class="fas fa-circle-check"></i> Активна
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                            <i class="fas fa-ban"></i> Неактивна
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
            <i class="fas fa-arrow-left"></i> К списку категорий
        </a>
    </div>

    {{-- Использование категории --}}
    @if(isset($usageCounts) && ($usageCounts['news'] > 0 || $usageCounts['pages'] > 0 || $usageCounts['children'] > 0))
        <div class="admin-hint px-4 py-3 mb-5 text-sm">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-medium flex items-center gap-2"><i class="fas fa-chart-simple"></i> Используется:</span>
                @if($usageCounts['news'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-newspaper text-indigo-500"></i> Новостей: <b>{{ $usageCounts['news'] }}</b>
                    </span>
                @endif
                @if($usageCounts['pages'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-file-lines text-indigo-500"></i> Страниц: <b>{{ $usageCounts['pages'] }}</b>
                    </span>
                @endif
                @if($usageCounts['children'] > 0)
                    <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1 text-xs">
                        <i class="fas fa-folder-tree text-indigo-500"></i> Дочерних категорий: <b>{{ $usageCounts['children'] }}</b>
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
        class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow p-6 max-w-2xl">

        @csrf
        @method('PUT')

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
                    value="{{ old('title', $category->title) }}"
                    maxlength="255"
                    autofocus
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
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
                    value="{{ old('slug', $category->slug) }}"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Будет сгенерирован автоматически">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Только латинские буквы, цифры и дефисы. Изменение slug обновит ссылки в меню.
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
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Краткое описание категории">{{ old('description', $category->description) }}</textarea>
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
                    value="{{ old('type', $category->type) }}"
                    maxlength="50"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
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
                    value="{{ old('icon', $category->icon) }}"
                    maxlength="100"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
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
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Корневая категория</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->title }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Для создания иерархии категорий. Нельзя выбрать саму категорию или её потомка.
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
                    value="{{ old('sort_order', $category->sort_order) }}"
                    min="0"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
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
                        {{ old('is_active', $category->is_active) ? 'checked' : '' }}
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
                    class="inline-flex items-center justify-center gap-2 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm shadow transition disabled:opacity-50">
                @themeIcon('save') Сохранить
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                @themeIcon('xmark') Отмена
            </a>

            <span class="text-xs text-gray-500 dark:text-gray-400 sm:ml-auto">
                Горячие клавиши: <b>Ctrl/Cmd + S</b> — сохранить, <b>Esc</b> — назад
            </span>
        </div>
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
