@extends('layouts.admin')

@section('title', 'Редактировать новость')

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
                            <i class="fas fa-circle-check"></i> Опубликовано
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                            <i class="fas fa-clock"></i> Черновик
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
                   title="Открыть на сайте">
                    <i class="fas fa-arrow-up-right-from-square"></i> На сайте
                </a>
            @endif
            <a href="{{ route('admin.news.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <div><b>Проверьте форму.</b> {{ $errors->first() }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}" enctype="multipart/form-data"
        class="space-y-5">
        @csrf
        @method('PUT')

        {{-- ── Основное ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-file-lines text-indigo-500"></i> Основное
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Заголовок" name="title" :value="$news->title" required
                    hint="Название новости. Отображается в заголовке и списке." />
                <x-admin.input label="URL (slug)" name="slug" :value="$news->slug"
                    hint="Только латинские буквы, цифры и дефисы. Изменение URL может повлиять на индексацию." />
                <x-admin.select label="Шаблон" name="template" :options="$templates" :selected="$news->template"
                    hint="Тип отображения: стандарт, товары и т.д." />
            </div>
        </div>

        {{-- ── SEO ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-magnifying-glass text-indigo-500"></i> SEO
            </h2>
            <div class="space-y-4">
                <x-admin.input label="Meta Title" name="meta_title" :value="$news->meta_title"
                    hint="До 60 символов. Заголовок вкладки и сниппета в поиске." />
                <x-admin.input label="Meta Description" name="meta_description" :value="$news->meta_description"
                    hint="До 160 символов. Краткое описание для поисковой выдачи." />
                <x-admin.input label="Ключевые слова" name="meta_keywords" :value="$news->meta_keywords"
                    hint="Через запятую: новости, мероприятия, экология" />

                {{-- Подсказка и разовая перезапись, если SEO-страница заблокирована --}}
                @if ($seoPage && !empty($seoPage->locked))
                    <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-200 p-3 text-sm">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-lock mt-0.5"></i>
                            <div>
                                <b>Внимание:</b> SEO-страница для этой новости <u>заблокирована</u> — правки из этой формы
                                не перезапишут SEO-данные. Разблокируйте запись в <b>SEO → Страницы</b> или отметьте
                                чекбокс ниже для <i>разовой</i> перезаписи.
                            </div>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="force_seo" value="1">
                        Перезаписать SEO для этой новости (игнорировать блокировку один раз)
                    </label>
                @endif
            </div>
        </div>

        {{-- ── Категории ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                <i class="fas fa-folder-open text-indigo-500"></i> Категории
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Выберите одну или несколько, чтобы классифицировать новость.</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($categories as $category)
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 cursor-pointer text-sm
                                  hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            {{ $news->categories->contains($category->id) ? 'checked' : '' }}>
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
                <x-admin.input label="Цена (₽)" name="price" type="number" step="0.01" :value="$news->price"
                    hint="Цена товара в рублях." />
                <x-admin.input label="Остаток" name="stock" type="number" :value="$news->stock"
                    hint="Сколько единиц товара доступно." />
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_promo" value="1" {{ $news->is_promo ? 'checked' : '' }}>
                    Акционный товар
                </label>
            </div>
        </div>

        {{-- ── Содержимое ── --}}
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-pen-nib text-indigo-500"></i> Содержимое
            </h2>
            <textarea name="content" id="editor" rows="14"
                class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-gray-100">{{ old('content', $news->content) }}</textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Основной текст публикации. Можно вставлять изображения, таблицы и видео.</p>
        </div>

        {{-- ── Публикация и сохранение ── --}}
        <div class="admin-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="published" value="1" {{ $news->published ? 'checked' : '' }}>
                Опубликовать
            </label>

            <div class="sm:ml-auto flex items-center gap-2">
                <a href="{{ route('admin.news.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Отмена
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-floppy-disk"></i> Сохранить изменения
                </button>
            </div>
        </div>
    
        {{-- Переводы контента на другие языки (таблица content_translations) --}}
        <x-admin.translations :model="$news" :fields="['title' => 'Заголовок', 'content' => ['label' => 'Текст новости', 'type' => 'textarea'], 'meta_title' => 'SEO: title', 'meta_description' => ['label' => 'SEO: description', 'type' => 'textarea']]" />

    </form>

    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 500,
            branding: false,
            license_key: 'gpl',
            convert_urls: false,
            automatic_uploads: true,
            plugins: 'image media link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code | removeformat',
            fontsize_formats: '10px 12px 14px 16px 18px 24px 36px',
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
                                alert('Ошибка: сервер не вернул ссылку на файл.');
                            }
                        })
                        .catch(error => {
                            alert('Ошибка загрузки файла: ' + error.message);
                        });
                };
                input.click();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template');
            const productFields = document.getElementById('product-fields');

            function toggleProductFields() {
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
