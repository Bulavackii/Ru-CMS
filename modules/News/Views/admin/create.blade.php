@extends('layouts.admin')

@section('title', 'Создать новость')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-plus"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Создание новости</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Заполните содержимое, SEO-поля и выберите шаблон отображения.</p>
            </div>
        </div>

        <a href="{{ route('admin.news.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0">
            <i class="fas fa-arrow-left"></i> К списку новостей
        </a>
    </div>

    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <div>
                    <b>Проверьте форму.</b> {{ $errors->first() }}
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="space-y-5 w-full">
        @csrf

        {{-- ── Основное ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-file-lines text-indigo-500"></i> Основное
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Заголовок" name="title" required
                    hint="Название новости. Отображается в заголовке и списке." />
                <x-admin.input label="URL (slug)" name="slug"
                    hint="Если не указан — сгенерируется из заголовка. Только латинские буквы, цифры и дефисы." />
                <x-admin.select label="Шаблон" name="template" :options="$templates"
                    hint="Тип отображения на сайте: стандартный, товары, отзывы и др." />
            </div>
        </div>

        {{-- ── SEO ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-magnifying-glass text-indigo-500"></i> SEO
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Meta Title" name="meta_title"
                    hint="До 60 символов. Заголовок вкладки и сниппета в поиске." />
                <div>
                    <label for="meta_description" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                    <textarea name="meta_description" id="meta_description" rows="3"
                        class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-gray-100
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="Краткое описание до 160 символов.">{{ old('meta_description') }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Отображается в поисковой выдаче. Включите ключевые фразы.</p>
                </div>
                <x-admin.input label="Ключевые слова" name="meta_keywords" hint="Через запятую: вода, природа, защита" />
            </div>
        </div>

        {{-- ── Категории ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                <i class="fas fa-folder-open text-indigo-500"></i> Категории
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Можно выбрать одну или несколько — для фильтрации и навигации.</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($categories as $category)
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm
                                  hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">Категорий пока нет.</p>
                @endforelse
            </div>
        </div>

        {{-- ── Поля шаблона «Товары» ── --}}
        <div id="product-fields" class="admin-card p-5 hidden animate-fade-in">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-bag-shopping text-indigo-500"></i> Товар
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Цена (₽)" name="price" type="number" step="0.01"
                    hint="Цена в рублях. Используется только в шаблоне «Товары»." />
                <x-admin.input label="Остаток" name="stock" type="number"
                    hint="Количество товара на складе. Целое число." />
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_promo" value="1" {{ old('is_promo') ? 'checked' : '' }}>
                    Акционный товар
                </label>
            </div>
        </div>

        {{-- ── Содержимое ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-pen-nib text-indigo-500"></i> Содержимое
            </h2>
            <textarea name="content" id="editor"
                class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-gray-100"
                rows="14">{{ old('content') }}</textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Основной текст новости. Поддерживает форматирование, изображения и видео.</p>
        
            {{-- Вставка сохранённой сборки каптчи в текст материала --}}
            @include('Captcha::partials.editor-picker')
</div>

        {{-- ── Публикация и сохранение ── --}}
        <div class="admin-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="published" value="1" checked>
                Опубликовать сразу
            </label>

            <div class="sm:ml-auto flex items-center gap-2">
                <a href="{{ route('admin.news.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Отмена
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-floppy-disk"></i> Сохранить новость
                </button>
            </div>
        </div>
    </form>

    {{-- TinyMCE --}}
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
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
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code | removeformat',
            fontsize_formats: '12px 14px 16px 18px 24px 36px',
            extended_valid_elements: 'iframe[src|frameborder|style|scrolling|class|width|height|name|align|allow|allowfullscreen|sandbox]',
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
                                alert('Ошибка загрузки.');
                            }
                        })
                        .catch(error => {
                            alert('Ошибка: ' + error.message);
                        });
                };
                input.click();
            }
        });

        // Показывать/скрывать блок "Товары"
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template');
            const productFields = document.getElementById('product-fields');
            const toggleFields = () => {
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
