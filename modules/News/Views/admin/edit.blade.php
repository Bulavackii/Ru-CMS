@extends('layouts.admin')

@section('title', 'Редактировать новость')

@section('content')
    <h1 class="text-2xl font-bold mb-6">✏️ Редактировать новость</h1>

    @if (\$errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow animate-pulse">
            <strong>Ошибка:</strong> {{ \$errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.update', ['news' => \$news->id]) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.input label="🔔 Заголовок" name="title" :value="\$news->title" required />
        <x-admin.input label="🔖 Meta Title" name="meta_title" :value="\$news->meta_title" hint="До 60 символов. Используйте «|» или «—»." />
        <x-admin.input label="📄 Meta Description" name="meta_description" :value="\$news->meta_description" hint="До 160 символов." />
        <x-admin.input label="🔑 Ключевые слова" name="meta_keywords" :value="\$news->meta_keywords" hint="Через запятую: акции, доставка" />
        <x-admin.select label="🧹 Шаблон" name="template" :options="\$templates" :selected="\$news->template" />

        <div>
            <label class="block mb-2 font-semibold">📂 Категории</label>
            <div class="flex flex-wrap gap-3">
                @foreach (\$categories as \$category)
                    <label class="flex items-center px-3 py-1 border border-gray-300 rounded-full cursor-pointer text-sm hover:bg-blue-50 transition">
                        <input type="checkbox" name="categories[]" value="{{ \$category->id }}" class="form-checkbox text-blue-600 mr-2" {{ \$news->categories->contains(\$category->id) ? 'checked' : '' }}>
                        {{ \$category->title }}
                    </label>
                @endforeach
            </div>
        </div>

        <div id="product-fields" class="mb-6 hidden">
            <x-admin.input label="💰 Цена" name="price" type="number" step="0.01" :value="\$news->price" />
            <x-admin.input label="📦 Остаток" name="stock" type="number" :value="\$news->stock" />
            <label class="inline-flex items-center text-sm text-gray-700">
                <input type="checkbox" name="is_promo" value="1" class="mr-2" {{ \$news->is_promo ? 'checked' : '' }}>
                🏷️ Акционный товар
            </label>
        </div>

        <div>
            <label for="editor" class="block mb-1 font-semibold">📝 Содержимое</label>
            <textarea name="content" id="editor" rows="14" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">{{ old('content', \$news->content) }}</textarea>
        </div>

        <label class="inline-flex items-center">
            <input type="checkbox" name="published" value="1" class="mr-2" {{ \$news->published ? 'checked' : '' }}>
            ✅ Опубликовать
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
            convert_urls: false,
            automatic_uploads: true,
            plugins: 'image media mediaembed link lists table code visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media mediaembed table | code | removeformat',
            fontsize_formats: '10px 12px 14px 16px 18px 24px 36px',
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
