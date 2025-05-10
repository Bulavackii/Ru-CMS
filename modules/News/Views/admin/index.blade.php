@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">📋 Список новостей</h1>
        <a href="{{ route('admin.news.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
            ➕ Добавить новость
        </a>
    </div>

    {{-- 🧭 Фильтр по шаблонам --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @php
            $icons = [
                'default' => '📰',
                'products' => '🛍️',
                'contacts' => '📇',
                'gallery' => '🖼️',
                'slideshow' => '🎞️',
                'faq' => '❓',
                'reviews' => '⭐',
                'test' => '🧪',
                'test2' => '⚙️',
            ];
            $currentTemplate = request('template');
            $currentCategory = request('category');
        @endphp

        <a href="{{ route('admin.news.index') }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border transition shadow-sm {{ !$currentTemplate ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            🗂️ Все шаблоны
        </a>

        @foreach ($templates as $key => $label)
            <a href="{{ route('admin.news.index', array_merge(request()->except('category'), ['template' => $key])) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium border transition shadow-sm {{ $currentTemplate === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $icons[$key] ?? '📄' }} {{ $label }}
            </a>
        @endforeach

        @if(count($categories))
            <select onchange="location = this.value" class="border rounded px-3 py-1.5 text-sm ml-auto">
                <option value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate])) }}">🗃️ Все категории</option>
                @foreach ($categories as $cat)
                    <option value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate, 'category' => $cat->id])) }}"
                            @if ($currentCategory == $cat->id) selected @endif>
                        🏷️ {{ $cat->title }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow animate-fade-in">
            ✅ {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.bulk') }}" id="bulk-form">
        @csrf
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <select name="action" class="border rounded px-3 py-2 text-sm">
                <option value="">🔽 Выберите действие</option>
                <option value="delete">🗑️ Удалить выбранные</option>
                <option value="edit">✏️ Массовое редактирование</option>
            </select>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm shadow transition-transform transform hover:scale-105">
                🚀 Применить
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white shadow-lg border border-black rounded-lg overflow-hidden">
                <thead class="bg-gray-200 text-sm text-gray-700">
                <tr>
                    <th class="px-4 py-3"><input type="checkbox" id="check-all"></th>
                    <th>📝 Заголовок</th>
                    <th>📂 Категории</th>
                    <th>🔖 Meta Title</th>
                    <th>🔑 Ключевые слова</th>
                    <th>📝 Meta Description</th>
                    <th>🛍️ Товар</th>
                    <th>📢 Статус</th>
                    <th>📦 Шаблон</th>
                    <th>⚙️ Действия</th>
                </tr>
                </thead>
                <tbody class="text-sm">
                @foreach ($newsList as $index => $news)
                    @php
                        $show = true;
                        if ($currentCategory) {
                            $show = $news->categories->contains('id', $currentCategory);
                        }
                    @endphp
                    @if ($show)
                        <tr class="transition-all duration-200 {{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }} hover:bg-blue-50">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="selected[]" value="{{ $news->id }}" class="row-checkbox">
                            </td>
                            <td class="px-4 py-3">{{ $news->title }}</td>
                            <td class="px-4 py-3">
                                @foreach ($news->categories as $cat)
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs rounded-full px-2 py-1 mr-1 mb-1">
                                        🏷️ {{ $cat->title }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3">{{ Str::limit($news->meta_title, 60) }}</td>
                            <td class="px-4 py-3">{{ Str::limit($news->meta_keywords, 60) }}</td>
                            <td class="px-4 py-3">{{ Str::limit($news->meta_description, 100) }}</td>
                            <td class="px-4 py-3">
                                @if ($news->template === 'products')
                                    💰 {{ number_format($news->price, 2, ',', ' ') }} ₽<br>
                                    📦 {{ $news->stock ?? 0 }} шт.<br>
                                    @if ($news->is_promo)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs text-white bg-pink-500 rounded-full">🔥 Акция</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $news->published ? '✅' : '🕒' }}</td>
                            <td class="px-4 py-3 text-center">{{ $icons[$news->template] ?? '📄' }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.news.edit', $news->id) }}" class="text-blue-600 hover:text-blue-800">✏️</a>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $newsList->withQueryString()->links() }}
        </div>
    </form>

    <script>
        document.getElementById('check-all')?.addEventListener('change', e =>
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked)
        );

        document.getElementById('bulk-form')?.addEventListener('submit', function(e) {
            const form = this;
            const action = form.querySelector('[name="action"]').value;
            const selected = [...form.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);

            if (!action) {
                e.preventDefault();
                alert('Выберите действие!');
                return;
            }

            if (!selected.length) {
                e.preventDefault();
                alert('Выберите хотя бы одну новость.');
                return;
            }

            if (action === 'edit') {
                e.preventDefault();
                const url = `{{ route('admin.news.bulk.edit') }}?ids=${selected.join(',')}`;
                window.location.href = url;
            }
        });
    </script>
@endsection
