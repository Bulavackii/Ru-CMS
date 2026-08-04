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

        {{-- ── Основное ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-file-lines text-indigo-500"></i> {{ __('admin.common.basic') }}
            </h2>
            <div class="space-y-4">
                <x-admin.input label="{{ __('admin.common.f_title') }}" name="title" required
                    hint="{{ __('admin.news.title_hint') }}" />
                <x-admin.input label="URL (slug)" name="slug"
                    hint="{{ __('admin.news.slug_hint') }}" />
                <x-admin.select label="{{ __('admin.common.f_template') }}" name="template" :options="$templates"
                    hint="{{ __('admin.news.template_hint') }}" />
            </div>
        </div>

        {{-- ── SEO ── --}}
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

        {{-- ── Категории ── --}}
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

        {{-- ── Поля шаблона «Товары» ── --}}
        <div id="product-fields" class="admin-card p-5 hidden animate-fade-in">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-bag-shopping text-indigo-500"></i> {{ __('admin.news.product') }}
            </h2>
            <div class="space-y-4">
                <x-admin.input label="{{ __('admin.news.price') }}" name="price" type="number" step="0.01"
                    hint="{{ __('admin.news.price_hint') }}" />
                <x-admin.input label="{{ __('admin.news.stock') }}" name="stock" type="number"
                    hint="{{ __('admin.news.stock_hint') }}" />
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_promo" value="1" {{ old('is_promo') ? 'checked' : '' }}>
                    {{ __('admin.news.sale') }}
                </label>
            </div>
        </div>

        {{-- ── Оценка: только для шаблона «Игры» ── --}}
        <div id="rating-fields" class="admin-card p-5 hidden animate-fade-in">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-star text-indigo-500"></i> {{ __('admin.news.rating_group') }}
            </h2>
            <x-admin.input label="{{ __('admin.news.rating') }}" name="rating" type="number"
                step="0.1" min="0" max="10" 
                hint="{{ __('admin.news.rating_hint') }}" />
        </div>


        {{-- ── Содержимое ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-pen-nib text-indigo-500"></i> {{ __('admin.common.content') }}
            </h2>
            <textarea name="content" id="editor"
                class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-gray-100"
                rows="14">{{ old('content') }}</textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.news.content_hint') }}</p>
        
            {{-- Вставка сохранённой сборки каптчи в текст материала --}}
            @include('Captcha::partials.editor-picker')
</div>

        {{-- ── Публикация и сохранение ── --}}
        <div class="admin-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="published" value="1" checked>
                {{ __('admin.news.publish_now') }}
            </label>

            <div class="sm:ml-auto flex items-center gap-2">
                <a href="{{ route('admin.news.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    {{ __('admin.cancel') }}
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-floppy-disk"></i> {{ __('admin.news.save') }}
                </button>
            </div>
        </div>
    </form>

    {{-- TinyMCE --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/editor-blocks.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 600,
            width: '100%',
            branding: false,
            license_key: 'gpl',
            convert_urls: false,
            plugins: 'image media link lists table code visualblocks wordcount',
            toolbar: 'undo redo | ruBlocks | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code | removeformat',
            fontsize_formats: '12px 14px 16px 18px 24px 36px',
            // К списку добавлены i и span: иконка — это пустой тег
            // <i class="fas ...">, а редактор по умолчанию вычищает пустые
            // инлайновые элементы, и значок исчезал при первом сохранении.
            extended_valid_elements: 'iframe[src|frameborder|style|scrolling|class|width|height|name|align|allow|allowfullscreen|sandbox],i[class|aria-hidden],span[class|style]',
            // Стили содержимого — те же, что на сайте. Редактор показывает
            // блок так, как его увидит посетитель, а не просто помечает
            // классом; body_class нужен, потому что все правила в файле
            // начинаются с .page-content.
            content_css: '{{ asset('assets/css/content-blocks.css') }}',
            body_class: 'page-content',

            // Готовые блоки в выпадающем списке «Стили». Без него имена
            // классов пришлось бы держать в голове или подсматривать в
            // соседней записи.
            // Кнопка «Блоки» — вставка готовых заготовок оформления.
            // Стандартный список «Стили» для этого не годился: он рисуется
            // выпадающим списком с подписью «Абзац», неотличимым от соседнего,
            // и найти блоки там было невозможно.
            setup: function (editor) {
                if (window.ruEditorBlocks) window.ruEditorBlocks(editor);
            },
            style_formats_merge: true,
            style_formats: [
                {
                    title: 'Блоки содержимого',
                    items: [
                        { title: 'Вводный абзац',        block: 'p',   classes: 'pc-lead' },
                        { title: 'Врезка-примечание',    block: 'p',   classes: 'pc-note' },
                        { title: 'Сетка карточек',       block: 'div', classes: 'pc-grid', wrapper: true },
                        { title: 'Карточка',             block: 'div', classes: 'pc-card', wrapper: true },
                        { title: 'Текст и картинка',     block: 'div', classes: 'pc-split', wrapper: true },
                        { title: 'Полоса призыва',       block: 'div', classes: 'pc-cta', wrapper: true },
                        { title: 'Список с галочками',   selector: 'ul', classes: 'pc-check' },
                        { title: 'Строка цифр',          selector: 'ul', classes: 'pc-stats' },
                        { title: 'Чипы технологий',      selector: 'ul', classes: 'pc-tech' },
                        { title: 'Нумерованные шаги',    selector: 'ol', classes: 'pc-steps' },
                        { title: 'Список вопросов',      block: 'div', classes: 'pc-faq', wrapper: true },
                        { title: 'Вопрос с ответом',     block: 'details', classes: 'pc-faq__item', wrapper: true },
                    ]
                }
            ],
            valid_children: '+body[iframe]',
            file_picker_types: 'image media',

            content_style: `
  body { font-family: system-ui; line-height: 1.6; }
  ul, ol { list-style-position: inside; text-align: left; padding-left: 0; margin-left: 0; }
  li { margin: 0.25rem 0; }

  /* === Визуалка выравниваний в TinyMCE === */
  img, video, iframe { max-width: 100%; height: auto; }
  figure.image { display: table; margin: 1rem auto; }

  /* центр */
  img.aligncenter, figure.image.align-center { display: block; margin: 0 auto; float: none; text-align: center; }
  /* влево / вправо */
  img.alignleft,  figure.image.align-left  { float: left;  margin: 0.25rem 1rem 1rem 0; }
  img.alignright, figure.image.align-right { float: right; margin: 0.25rem 0 1rem 1rem; }

  /* случай, когда центр задаётся через text-align у родителя */
  p[style*="text-align: center"] img { display: inline-block; }
  /* clearfix */
  .mce-content-body:after { content: ""; display: block; clear: both; }
`,


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
                        .catch(error => {
                            alert(@js(__('admin.common.error')) + ' ' + error.message);
                        });
                };
                input.click();
            }
        });

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
