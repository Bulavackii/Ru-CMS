@extends('layouts.admin')

@section('title', 'Создать новость')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📝 Создание новости</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 dark:bg-red-900 dark:border-red-700 dark:text-red-200 px-4 py-3 mb-6 rounded shadow animate-pulse">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="space-y-6 w-full">
        @csrf

        {{-- Заголовок --}}
        <x-admin.input label="📰 Заголовок" name="title" required hint="Название новости. Отображается в заголовке и списке." />

        {{-- Meta Title --}}
        <x-admin.input label="🔖 Meta Title" name="meta_title" hint="До 60 символов. Отображается в заголовке вкладки и в поисковых системах." />

        {{-- Meta Description --}}
        <div>
            <label for="meta_description" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">📄 Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100"
                      placeholder="Краткое описание до 160 символов.">{{ old('meta_description') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Отображается в поисковой выдаче. Включите ключевые фразы.</p>
        </div>

        {{-- Meta Keywords --}}
        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" hint="Через запятую: вода, природа, защита" />

        {{-- Шаблон --}}
        <x-admin.select label="🧩 Шаблон" name="template" :options="$templates" hint="Выберите шаблон отображения: стандартный, товары, отзывы и др." />

        {{-- Категории --}}
        <div>
            <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">📂 Категории</label>
            <p class="text-sm text-gray-500 mb-2">Можно выбрать одну или несколько категорий для фильтрации и навигации.</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-full cursor-pointer text-sm hover:bg-blue-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               class="form-checkbox text-blue-600 mr-2"
                               {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Поля для "Товары" --}}
        <div id="product-fields" class="mb-6 hidden animate-fade-in">
            <x-admin.input label="💰 Цена (₽)" name="price" type="number" step="0.01" hint="Цена в рублях. Используется только в шаблоне 'Товары'." />
            <x-admin.input label="📦 Остаток" name="stock" type="number" hint="Количество товара на складе. Целое число." />
            <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="is_promo" value="1" {{ old('is_promo') ? 'checked' : '' }} class="mr-2">
                🏷️ Акционный товар
            </label>
        </div>

        {{-- Контент --}}
        <div>
            <label for="editor" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">📝 Содержимое</label>
            <textarea name="content" id="editor"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100"
                      rows="14">{{ old('content') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Основной текст новости. Поддерживает форматирование, изображения и видео.</p>
        </div>

        {{-- Публикация --}}
        <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="published" value="1" class="mr-2" checked>
            Опубликовать сразу
        </label>

        {{-- Кнопка --}}
        <div class="pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                💾 Сохранить новость
            </button>
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
            convert_urls: false,
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media mediaembed table | code | removeformat',
            fontsize_formats: '12px 14px 16px 18px 24px 36px',
            extended_valid_elements: 'iframe[src|frameborder|style|scrolling|class|width|height|name|align|allow|allowfullscreen|sandbox]',
            valid_children: '+body[iframe]',
            file_picker_types: 'image media',

            content_style: `
                body { font-family: system-ui; line-height: 1.6; }
                ul, ol {
                    list-style-position: inside;
                    text-align: left;
                    padding-left: 0;
                    margin-left: 0;
                }
                li {
                    margin: 0.25rem 0;
                }
            `,

            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = meta.filetype === 'image' ? 'image/*' : 'video/*';
                input.onchange = function () {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('file', file);
                    fetch('{{ route('admin.upload.media') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.location) {
                            callback(data.location, { title: file.name });
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
        document.addEventListener('DOMContentLoaded', function () {
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
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
@endsection
