@extends('layouts.admin')

@section('title', 'Редактировать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-6">✏️ Редактировать новость</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow animate-pulse">
            <strong>Ошибка:</strong> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.update', ['news' => $news->id]) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Заголовок --}}
        <div class="mb-6 max-w-xl">
            <label for="title" class="block mb-1 font-semibold">📰 Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title', $news->title) }}"
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                   required>
        </div>

        {{-- Meta Title --}}
        <div class="mb-4 max-w-xl">
            <label for="meta_title" class="block mb-1 font-semibold">🔖 Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $news->meta_title) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2"
                   placeholder="Например: Новости компании | RuShop">
            <p class="text-xs text-gray-500 mt-1">
                Заголовок страницы в поисковой выдаче (до 60 символов). Используйте ключевые слова и разделяйте блоки через «|» или «—».
            </p>
        </div>

        {{-- Meta Description --}}
        <div class="mb-4 max-w-xl">
            <label for="meta_description" class="block mb-1 font-semibold">📄 Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2"
                      placeholder="Краткое описание до 160 символов.">{{ old('meta_description', $news->meta_description) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">
                Краткое описание (до 160 символов) — используется в сниппете поисковика. Включайте ключевые слова и цепляющий текст.
            </p>
        </div>

        {{-- Meta Keywords --}}
        <div class="mb-6 max-w-xl">
            <label for="meta_keywords" class="block mb-1 font-semibold">🔑 Ключевые слова</label>
            <input type="text" name="meta_keywords" id="meta_keywords" value="{{ old('meta_keywords', $news->meta_keywords) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2"
                   placeholder="Например: акции, доставка, отзывы, RuShop">
            <p class="text-xs text-gray-500 mt-1">
                Перечислите ключевые слова через <strong>запятую</strong> или <strong>пробел</strong>, например: <em>товары, скидки, интернет-магазин</em>.
            </p>
        </div>

        {{-- Категории --}}
        <div>
            <label class="block mb-2 font-semibold">📂 Категории</label>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center px-3 py-1 border border-gray-300 rounded-full cursor-pointer text-sm hover:bg-blue-50 transition">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               class="form-checkbox text-blue-600 mr-2"
                               {{ $news->categories->contains($category->id) ? 'checked' : '' }}>
                        {{ $category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Шаблон --}}
        <div class="mb-6 max-w-xs">
            <label for="template" class="block mb-1 font-semibold">🧩 Шаблон</label>
            <select name="template" id="template"
                    class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                @foreach ($templates as $value => $label)
                    <option value="{{ $value }}" {{ old('template', $news->template) == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Цена, Остаток, Промо --}}
        <div id="product-fields" class="mb-4 hidden">
            <div class="mb-3">
                <label for="price" class="block font-semibold mb-1">💰 Цена</label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price', $news->price) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-3">
                <label for="stock" class="block font-semibold mb-1">📦 Остаток на складе</label>
                <input type="number" name="stock" id="stock" value="{{ old('stock', $news->stock) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_promo" value="1" class="mr-2"
                           {{ old('is_promo', $news->is_promo) ? 'checked' : '' }}>
                    🏷️ Акционный товар
                </label>
            </div>
        </div>

        {{-- Контент --}}
        <div>
            <label for="content" class="block mb-1 font-semibold">📝 Содержимое</label>
            <textarea name="content" id="editor" rows="12"
                      class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">{{ old('content', $news->content) }}</textarea>
        </div>

        {{-- Публикация --}}
        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" name="published" value="1" class="mr-2"
                       {{ old('published', $news->published) ? 'checked' : '' }}>
                ✅ Опубликовать
            </label>
        </div>

        <div class="pt-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
                💾 Сохранить изменения
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
            height: 500,
            branding: false,
            convert_urls: false,
            automatic_uploads: true,
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media mediaembed table | code | removeformat',
            fontsize_formats: '10px 12px 14px 16px 18px 24px 36px',
            extended_valid_elements: 'iframe[src|frameborder|style|scrolling|class|width|height|name|align|allow|allowfullscreen|sandbox]',
            valid_children: '+body[iframe]',
            file_picker_types: 'image media',
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

        document.addEventListener('DOMContentLoaded', function () {
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
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
@endsection
