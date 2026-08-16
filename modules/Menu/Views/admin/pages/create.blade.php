@extends('layouts.admin')

@section('title', __('admin.pages.page_create'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    {{-- Та же шапка в два ряда, что у правки. --}}
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fa-solid fa-file-circle-plus"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">{{ __('admin.pages.create') }}</h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pages.create_hint') }}</p>

            <a href="{{ route('admin.pages.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fa-solid fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
            </a>
        </div>
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

        {{-- Две колонки, как в Новостях и Категориях: слева то, над чем
             работают, справа служебное. Одним столбцом во всю ширину короткое
             поле адреса растягивалось на весь экран. --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

            {{-- ── Левая колонка ── --}}
            <div class="xl:col-span-2 space-y-5">

                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-indigo-500"></i> {{ __('admin.common.basic') }}
                    </h2>
                    <div class="space-y-4">
                        <x-admin.input label="{{ __('admin.common.f_title') }}" name="title" :value="old('title')" required
                            hint="{{ __('admin.pages.title_hint') }}" />
                        {{-- Адрес — короткое значение, ему половины строки хватает. --}}
                        <div class="md:w-1/2">
                            <x-admin.input label="{{ __('admin.common.f_slug') }}" name="slug" :value="old('slug')"
                                hint="{{ __('admin.pages.slug_hint') }}" />
                        </div>
                    </div>
                </div>

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
            </div>

            {{-- ── Правая колонка ── --}}
            <div class="space-y-5">

                {{-- Публикация наверху: это то, ради чего сюда заходят, и
                     мотать за кнопкой вниз через весь редактор незачем.
                     Тумблеры вместо галочек — состояние читается сразу, а
                     подпись говорит, что оно значит для посетителя. --}}
                <div class="admin-card p-5 space-y-4" x-data="{ live: true, home: false }">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-indigo-500"></i> {{ __('admin.common.publication') }}
                    </h2>

                    <div class="flex items-start gap-3">
                        <label class="admin-toggle mt-0.5">
                            <input type="checkbox" name="published" value="1" x-model="live" {{ old('published') ? 'checked' : '' }}>
                            <span class="track"></span>
                            <span class="knob"></span>
                        </label>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white"
                               x-text="live ? @js(__('admin.pages.publish')) : @js(__('admin.news.state_draft'))"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"
                               x-text="live ? @js(__('admin.news.state_published_hint')) : @js(__('admin.news.state_draft_hint'))"></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <label class="admin-toggle mt-0.5">
                            <input type="checkbox" name="show_on_homepage" value="1" x-model="home" {{ old('show_on_homepage') ? 'checked' : '' }}>
                            <span class="track"></span>
                            <span class="knob"></span>
                        </label>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.pages.show_on_home') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.pages.home_order_hint') }}</p>
                        </div>
                    </div>

                    {{-- Порядок нужен только тем страницам, что показаны на
                         главной: без этого поле стоит вечно и сбивает с толку. --}}
                    <div x-show="home" x-cloak class="flex items-center gap-3">
                        <label for="homepage_order" class="text-sm text-gray-700 dark:text-gray-300 flex-none">{{ __('admin.pages.home_order') }}</label>
                        <input type="number" name="homepage_order" id="homepage_order" value="{{ old('homepage_order') }}"
                            class="w-24 border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-800 dark:text-gray-100
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 flex-1 mt-3 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                            <i class="fa-solid fa-floppy-disk"></i> {{ __('admin.pages.save') }}
                        </button>
                        <a href="{{ route('admin.pages.index') }}"
                           class="inline-flex items-center mt-3 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                                  text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('admin.cancel') }}
                        </a>
                    </div>
                </div>

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
            </div>
        </div>

        {{-- Содержимое — отдельной строкой во всю ширину: текст здесь главное,
             и делить место с полями настроек ему незачем. --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-indigo-500"></i> {{ __('admin.common.content') }}
            </h2>
            <x-ru-editor name="content" id="editor" :height="560"
                          :placeholder="__('admin.pages.content_hint')" />
        </div>

    </form>

    {{-- 🧠 TinyMCE редактор --}}
@endsection
