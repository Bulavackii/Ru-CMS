@extends('layouts.admin')

@section('title', __('admin.news.page_create'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-plus"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.news.create') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.news.create_hint') }}</p>
            </div>
        </div>

        <a href="{{ route('admin.news.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.news.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <div>
                    <b>{{ __('admin.common.check_form') }}</b> {{ $errors->first() }}
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="space-y-5 w-full">
        @csrf

        {{-- Две колонки, как на правке и в Категориях: слева то, над чем
             работают, справа служебное. Одним столбцом во всю ширину короткие
             поля растягивались на полторы тысячи пикселей. --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

            {{-- ── Левая колонка ── --}}
            <div class="xl:col-span-2 space-y-5">

                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-file-lines text-indigo-500"></i> {{ __('admin.common.basic') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-admin.input label="{{ __('admin.common.f_title') }}" name="title" required
                                hint="{{ __('admin.news.title_hint') }}" />
                        </div>
                        <x-admin.input label="URL (slug)" name="slug"
                            hint="{{ __('admin.news.slug_hint') }}" />
                        <x-admin.select label="{{ __('admin.common.f_template') }}" name="template" :options="$templates"
                            hint="{{ __('admin.news.template_hint') }}" />
                    </div>
                </div>

                {{-- Поля шаблона «Товары» --}}
                <div id="product-fields" class="admin-card p-5 hidden animate-fade-in">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-bag-shopping text-indigo-500"></i> {{ __('admin.news.product') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-admin.input label="{{ __('admin.news.price') }}" name="price" type="number" step="0.01"
                            hint="{{ __('admin.news.price_hint') }}" />
                        <x-admin.input label="{{ __('admin.news.stock') }}" name="stock" type="number"
                            hint="{{ __('admin.news.stock_hint') }}" />
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_promo" value="1" {{ old('is_promo') ? 'checked' : '' }}>
                            {{ __('admin.news.sale') }}
                        </label>
                    </div>
                </div>

                {{-- Оценка: только для шаблона «Игры» --}}
                <div id="rating-fields" class="admin-card p-5 hidden animate-fade-in">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-star text-indigo-500"></i> {{ __('admin.news.rating_group') }}
                    </h2>
                    <div class="md:w-1/2">
                        <x-admin.input label="{{ __('admin.news.rating') }}" name="rating" type="number"
                            step="0.1" min="0" max="10"
                            hint="{{ __('admin.news.rating_hint') }}" />
                    </div>
                </div>
            </div>

            {{-- ── Правая колонка ── --}}
            <div class="space-y-5">

                <div class="admin-card p-5 space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas fa-circle-check text-indigo-500"></i> {{ __('admin.news.publish') }}
                    </h2>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="published" value="1" checked>
                        {{ __('admin.news.publish_now') }}
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                            <i class="fas fa-floppy-disk"></i> {{ __('admin.news.save') }}
                        </button>
                        <a href="{{ route('admin.news.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                                  text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('admin.cancel') }}
                        </a>
                    </div>
                </div>

                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                        <i class="fas fa-folder-open text-indigo-500"></i> {{ __('admin.common.categories') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('admin.news.categories_hint') }}</p>
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

                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-magnifying-glass text-indigo-500"></i> SEO
                    </h2>
                    <div class="space-y-4">
                        <x-admin.input label="Meta Title" name="meta_title"
                            hint="{{ __('admin.news.meta_title_hint') }}" />
                        <div>
                            <label for="meta_description" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                            <textarea name="meta_description" id="meta_description" rows="3"
                                class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-gray-100
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="{{ __('admin.news.desc_hint') }}">{{ old('meta_description') }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.news.seo_hint') }}</p>
                        </div>
                        <x-admin.input label="{{ __('admin.common.f_keywords') }}" name="meta_keywords" hint="{{ __('admin.news.keywords_hint') }}" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Содержимое — отдельной строкой во всю ширину.

             В колонке редактор делил место с полями настроек и получал две
             трети экрана. Текст здесь главное: чем шире полоса набора, тем
             ближе она к тому, что увидит посетитель, и тем меньше переносов
             там, где их на сайте не будет. --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-pen-nib text-indigo-500"></i> {{ __('admin.common.content') }}
            </h2>
            <x-ru-editor name="content" id="editor" :height="520" />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.news.content_hint') }}</p>
        </div>
    </form>

    {{-- TinyMCE --}}
    <script>


        // Показывать/скрывать блок "Товары"
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template');
            const productFields = document.getElementById('product-fields');
            // Оценка показывается только у шаблона «Игры»: у остальных
            // материалов это поле бессмысленно и только путало бы редактора.
            const ratingFields = document.getElementById('rating-fields');

            const toggleFields = () => {
                if (ratingFields) {
                    ratingFields.classList.toggle('hidden', templateSelect.value !== 'gaming');
                }

                if (templateSelect.value === 'products') {
                    productFields.classList.remove('hidden');
                } else {
                    productFields.classList.add('hidden');
                }
            };
            templateSelect.addEventListener('change', toggleFields);
            toggleFields();
        });
    </script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
@endsection
