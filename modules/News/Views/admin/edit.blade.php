@extends('layouts.admin')

@section('title', __('admin.news.page_edit'))

@section('content')
    @php
        // найдём связанную SEO-страницу по slug
        $seoSlug = '/news/' . ltrim((string) $news->slug, '/');
        $seoPage = \Modules\Seo\Models\SeoPage::where('slug', $seoSlug)->first();
    @endphp

    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-pen"></i></span>
            <div class="min-w-0 space-y-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $news->title }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if ($news->published)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                            <i class="fas fa-circle-check"></i> {{ __('admin.common.published') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                            <i class="fas fa-clock"></i> {{ __('admin.common.draft') }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        ID {{ $news->id }}
                    </span>
                    @if ($news->slug)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 font-mono">
                            /news/{{ $news->slug }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if ($news->slug && $news->published)
                <a href="{{ url('/news/' . $news->slug) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                   title="{{ __('admin.common.open_on_site') }}">
                    <i class="fas fa-arrow-up-right-from-square"></i> {{ __('admin.common.on_site') }}
                </a>
            @endif
            <a href="{{ route('admin.news.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                <i class="fas fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <div><b>{{ __('admin.common.check_form') }}</b> {{ $errors->first() }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}" enctype="multipart/form-data"
        class="space-y-5">
        @csrf
        @method('PUT')

        {{-- Две колонки, как в Категориях: слева то, над чем работают
             (заголовок, текст, поля шаблона), справа служебное — публикация,
             разделы, SEO.

             До этого всё лежало одним столбцом во всю ширину, и на широком
             экране поле для десятизначного адреса растягивалось на полторы
             тысячи пикселей: короткое значение теряется в пустой строке, а
             глазу не за что зацепиться. --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

            {{-- ── Левая колонка: над чем работают ── --}}
            <div class="xl:col-span-2 space-y-5">

                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-file-lines text-indigo-500"></i> {{ __('admin.common.basic') }}
                    </h2>
                    {{-- Адрес и шаблон — короткие значения, им хватает половины
                         строки; заголовок остаётся во всю ширину. --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-admin.input label="{{ __('admin.common.f_title') }}" name="title" :value="$news->title" required
                                hint="{{ __('admin.news.title_hint') }}" />
                        </div>
                        <x-admin.input label="URL (slug)" name="slug" :value="$news->slug"
                            hint="{{ __('admin.news.slug_hint_edit') }}" />
                        <x-admin.select label="{{ __('admin.common.f_template') }}" name="template" :options="$templates" :selected="$news->template"
                            hint="{{ __('admin.news.template_hint_edit') }}" />
                    </div>
                </div>

                {{-- Поля шаблона «Товары» --}}
                <div id="product-fields" class="admin-card p-5 hidden animate-fade-in">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-bag-shopping text-indigo-500"></i> {{ __('admin.news.product') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-admin.input label="{{ __('admin.news.price') }}" name="price" type="number" step="0.01" :value="$news->price"
                            hint="{{ __('admin.news.price_hint_edit') }}" />
                        <x-admin.input label="{{ __('admin.news.stock') }}" name="stock" type="number" :value="$news->stock"
                            hint="{{ __('admin.news.stock_hint_edit') }}" />
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_promo" value="1" {{ $news->is_promo ? 'checked' : '' }}>
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
                            step="0.1" min="0" max="10" :value="$news->rating"
                            hint="{{ __('admin.news.rating_hint') }}" />
                    </div>
                </div>
            </div>

            {{-- ── Правая колонка: служебное ── --}}
            <div class="space-y-5">

                {{-- Публикация и сохранение наверху: это то, ради чего сюда
                     заходят, и мотать за кнопкой вниз через весь редактор
                     незачем. --}}
                <div class="admin-card p-5 space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas fa-circle-check text-indigo-500"></i> {{ __('admin.news.publish') }}
                    </h2>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="published" value="1" {{ $news->published ? 'checked' : '' }}>
                        {{ __('admin.news.publish') }}
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                            <i class="fas fa-floppy-disk"></i> {{ __('admin.common.save_changes') }}
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
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('admin.news.categories_hint_edit') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($categories as $category)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm
                                          hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                    {{ $news->categories->contains($category->id) ? 'checked' : '' }}>
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
                        <x-admin.input label="Meta Title" name="meta_title" :value="$news->meta_title"
                            hint="{{ __('admin.news.meta_title_hint') }}" />
                        <x-admin.input label="Meta Description" name="meta_description" :value="$news->meta_description"
                            hint="{{ __('admin.news.meta_desc_hint') }}" />
                        <x-admin.input label="{{ __('admin.common.f_keywords') }}" name="meta_keywords" :value="$news->meta_keywords"
                            hint="{{ __('admin.news.keywords_hint_edit') }}" />

                        {{-- Подсказка и разовая перезапись, если SEO-страница заблокирована --}}
                        @if ($seoPage && !empty($seoPage->locked))
                            <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-200 p-3 text-sm">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-lock mt-0.5"></i>
                                    <div>
                                        <b>{{ __('admin.news.attention') }}</b> {{ __('admin.news.seo_locked_1') }} <u>{{ __('admin.news.seo_locked_2') }}</u> {{ __('admin.news.seo_locked_3') }}
                                        {{ __('admin.news.seo_locked_3') }} <b>SEO</b> {{ __('admin.news.seo_locked_4') }}
                                        <i>{{ __('admin.news.seo_locked_5') }}</i> {{ __('admin.news.seo_locked_6') }}
                                    </div>
                                </div>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="force_seo" value="1">
                                {{ __('admin.news.seo_override') }}
                            </label>
                        @endif
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
            <x-ru-editor name="content" id="editor" :value="$news->content" :height="520" />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.news.content_hint_edit') }}</p>
        </div>

        {{-- Переводы контента на другие языки (таблица content_translations) --}}
        <x-admin.translations :model="$news" :fields="['title' => __('admin.common.f_title'), 'content' => ['label' => __('admin.news.tr_content'), 'type' => 'textarea'], 'meta_title' => 'SEO: title', 'meta_description' => ['label' => 'SEO: description', 'type' => 'textarea']]" />

    </form>

    <script>


        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template');
            const productFields = document.getElementById('product-fields');
            // Оценка нужна только шаблону «Игры»: у остальных материалов это
            // поле бессмысленно и только путало бы редактора.
            const ratingFields = document.getElementById('rating-fields');

            function toggleProductFields() {
                if (ratingFields) {
                    ratingFields.classList.toggle('hidden', templateSelect.value !== 'gaming');
                }

                if (templateSelect.value === 'products') {
                    productFields.classList.remove('hidden');
                    productFields.classList.add('animate-fade-in');
                } else {
                    productFields.classList.add('hidden');
                }
            }
            templateSelect.addEventListener('change', toggleProductFields);
            toggleProductFields();
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
            animation: fadeIn 0.4s ease-out;
        }
    </style>
@endsection
