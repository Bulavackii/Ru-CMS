@extends('layouts.admin')

@section('title', 'Редактировать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-6">✏️ Редактировать новость</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow animate-pulse">
            <strong>Ошибка:</strong> {{ $errors->first() }}
        </div>
    @endif

    @php
        // найдём связанную SEO-страницу по slug
        $seoSlug = '/news/' . ltrim((string) $news->slug, '/');
        $seoPage = \Modules\Seo\Models\SeoPage::where('slug', $seoSlug)->first();
    @endphp

    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}" enctype="multipart/form-data"
        class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.input label="🔔 Заголовок" name="title" :value="$news->title" required
            hint="Название новости. Отображается в заголовке и списке." />
        
        <x-admin.input label="🔗 URL (slug)" name="slug" :value="$news->slug"
            hint="URL-адрес новости. Только латинские буквы, цифры и дефисы. Изменение URL может повлиять на индексацию в поисковых системах." />
        
        <x-admin.input label="🔖 Meta Title" name="meta_title" :value="$news->meta_title"
            hint="До 60 символов. Используется в заголовке вкладки и SEO." />
        <x-admin.input label="📄 Meta Description" name="meta_description" :value="$news->meta_description"
            hint="До 160 символов. Краткое описание для поисковой выдачи." />
        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" :value="$news->meta_keywords"
            hint="Через запятую: новости, мероприятия, экология" />

        {{-- 🔒 Подсказка и разовая перезапись, если SEO-страница заблокирована --}}
        @if ($seoPage && !empty($seoPage->locked))
            <div class="rounded-lg border border-amber-300 bg-amber-50 text-amber-900 p-3">
                <div class="flex items-start gap-2">
                    <span class="text-amber-600">@themeIcon('lock')</span>
                    <div class="text-sm">
                        <b>Внимание:</b> SEO-страница для этой новости <u>заблокирована</u>. Правки из этой формы не
                        перезапишут SEO-данные.
                        Разблокируйте запись в <b>SEO → Страницы</b> или отметьте чекбокс ниже для <i>разовой</i>
                        перезаписи.
                    </div>
                </div>
            </div>
            <label class="inline-flex items-center text-sm">
                <input type="checkbox" name="force_seo" value="1" class="mr-2">
                Перезаписать SEO для этой новости (игнорировать блокировку один раз)
            </label>
        @endif

        <x-admin.select label="🧹 Шаблон" name="template" :options="$templates" :selected="$news->template"
            hint="Выберите тип отображения: стандарт, товары и т.д." />

        <div>
            <label class="block mb-2 font-semibold text-gray-700 dark:text-gray-300">📂 Категории</label>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Выберите одну или несколько категорий, чтобы
                классифицировать новость.</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label
                        class="flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-full cursor-pointer text-sm hover:bg-blue-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            class="form-checkbox text-blue-600 mr-2"
                            {{ $news->categories->contains($category->id) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        <div id="product-fields" class="mb-6 hidden animate-fade-in">
            <x-admin.input label="💰 Цена" name="price" type="number" step="0.01" :value="$news->price"
                hint="Укажите цену товара в рублях." />
            <x-admin.input label="📦 Остаток" name="stock" type="number" :value="$news->stock"
                hint="Сколько единиц товара доступно." />
            <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="is_promo" value="1" class="mr-2" {{ $news->is_promo ? 'checked' : '' }}>
                🏷️ Акционный товар
            </label>
        </div>

        <div>
            <label for="editor" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">📝 Содержимое</label>
            <textarea name="content" id="editor" rows="14"
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring focus:ring-blue-200">{{ old('content', $news->content) }}</textarea>
            <p class="text-sm text-gray-500 mt-1">Основной текст публикации. Можно вставлять изображения, таблицы и видео.
            </p>
        </div>

        <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="published" value="1" class="mr-2" {{ $news->published ? 'checked' : '' }}>
            Опубликовать
        </label>

        <div class="pt-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
                💾 Сохранить изменения
            </button>
        </div>
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
