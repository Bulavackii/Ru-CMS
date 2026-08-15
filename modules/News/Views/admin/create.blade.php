@extends('layouts.admin')

{{-- Какие поля какому шаблону — из констант контроллера, а не своей
     копией списка: копий уже было четыре и они разошлись (цена у услуги
     обнулялась при сохранении). --}}
@php
    $сЦеной    = \Modules\News\Controllers\Admin\NewsController::PRICE_TEMPLATES;
    $сОстатком = \Modules\News\Controllers\Admin\NewsController::STOCK_TEMPLATES;
    $сОценкой  = \Modules\News\Controllers\Admin\NewsController::RATING_TEMPLATES;
@endphp


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
                        {{-- Оценка — тонкой строкой внутри «Основного», а не
                             отдельной карточкой. Одно число в карточке на всю
                             ширину оставляло под собой пустоту в полсотни
                             пикселей и растягивало левую колонку ниже правой. --}}
                        <div id="rating-fields" class="md:col-span-2 hidden animate-fade-in">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border border-gray-200 dark:border-gray-700 px-4 py-3">
                                <label for="rating" class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2 flex-none">
                                    <i class="fas fa-star text-indigo-500"></i> {{ __('admin.news.rating') }}
                                </label>
                                <input type="number" name="rating" id="rating" step="0.1" min="0" max="10"
                                    value="{{ old('rating') }}"
                                    class="w-24 flex-none border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-800 dark:text-gray-100
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                <span class="text-xs text-gray-500 dark:text-gray-400 flex-1 min-w-0">{{ __('admin.news.rating_hint') }}</span>
                            </div>
                        </div>
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

                {{-- Цена. Носят «Товары» И «Услуги»: у услуги она стоит в
                     карточке с оговоркой «от». Блок отделён от остатка —
                     склада у услуги нет, а раньше поля шли вместе, и цена
                     была доступна только товару. --}}
                <div id="price-fields" class="admin-card p-5 hidden animate-fade-in">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-ruble-sign text-indigo-500"></i> {{ __('admin.news.price') }}
                    </h2>
                    <x-admin.input label="{{ __('admin.news.price') }}" name="price" type="number" step="0.01"
                            hint="{{ __('admin.news.price_hint') }}" />
                </div>

                {{-- Остаток и распродажа — только товарные. --}}
                <div id="product-fields" class="admin-card p-5 hidden animate-fade-in">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-bag-shopping text-indigo-500"></i> {{ __('admin.news.product') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-admin.input label="{{ __('admin.news.stock') }}" name="stock" type="number"
                            hint="{{ __('admin.news.stock_hint') }}" />
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_promo" value="1" {{ old('is_promo') ? 'checked' : '' }}>
                            {{ __('admin.news.sale') }}
                        </label>
                    </div>
                </div>

                {{-- Оценка: только для шаблона «Игры» --}}
            </div>

            {{-- ── Правая колонка ── --}}
            <div class="space-y-5">

                <div class="admin-card p-5 space-y-4" x-data="{ live: true , home: {{ old('show_on_homepage', true) ? 'true' : 'false' }} }">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas fa-circle-check text-indigo-500"></i> {{ __('admin.news.publish') }}
                    </h2>

                    {{-- Тумблер вместо голой галочки: состояние читается с
                         одного взгляда, а подпись рядом прямо говорит, что это
                         значит для посетителя. Галочка сообщала только «есть
                         или нет», и приходилось помнить, что она включает. --}}
                    <div class="flex items-start gap-3">
                        <label class="admin-toggle mt-0.5">
                            <input type="checkbox" name="published" value="1" x-model="live" checked>
                            <span class="track"></span>
                            <span class="knob"></span>
                        </label>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white"
                               x-text="live ? @js(__('admin.news.publish_now')) : @js(__('admin.news.state_draft'))"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"
                               x-text="live ? @js(__('admin.news.state_published_hint')) : @js(__('admin.news.state_draft_hint'))"></p>
                        </div>
                    </div>

                    {{-- «Показать на главной» — тот же переключатель, что у
                         страниц: разделы должны вести себя одинаково.

                         ⚠️ Новый материал приходит с ВКЛЮЧЁННЫМ переключателем,
                         в отличие от страниц. Главная и есть витрина новостей:
                         выключенное умолчание означало бы, что написанная
                         новость нигде не появляется, пока автор не догадается
                         щёлкнуть ещё один тумблер. --}}
                    <div class="flex items-start gap-3">
                        <label class="admin-toggle mt-0.5">
                            <input type="checkbox" name="show_on_homepage" value="1" x-model="home"
                                   {{ old('show_on_homepage', true) ? 'checked' : '' }}>
                            <span class="track"></span>
                            <span class="knob"></span>
                        </label>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.common.show_on_home') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.common.home_order_hint') }}</p>
                        </div>
                    </div>

                    <div x-show="home" x-cloak class="flex items-center gap-3">
                        <label for="homepage_order" class="text-sm text-gray-700 dark:text-gray-300 flex-none">{{ __('admin.common.home_order') }}</label>
                        <input type="number" name="homepage_order" id="homepage_order" min="0"
                               value="{{ old('homepage_order') }}"
                               class="w-24 border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-800 dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 flex-1 mt-3 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                            <i class="fas fa-floppy-disk"></i> {{ __('admin.news.save') }}
                        </button>
                        <a href="{{ route('admin.news.index') }}"
                           class="inline-flex items-center mt-3 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                                  text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('admin.cancel') }}
                        </a>
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
            const priceFields = document.getElementById('price-fields');
            // Оценку показываем шаблонам, где она осмысленна: «Игры» и
            // «Отзывы». У остальных поле только путало бы редактора.
            const ratingFields = document.getElementById('rating-fields');

            const toggleFields = () => {
                // Списки приходят с сервера из констант контроллера: своя
                // копия здесь уже расходилась с серверной проверкой.
                const т = templateSelect.value;

                if (ratingFields)  ratingFields.classList.toggle('hidden', !@js($сОценкой).includes(т));
                if (priceFields)   priceFields.classList.toggle('hidden', !@js($сЦеной).includes(т));
                if (productFields) productFields.classList.toggle('hidden', !@js($сОстатком).includes(т));
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
