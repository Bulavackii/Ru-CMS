@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📋 Список новостей</h1>
        <a href="{{ route('admin.news.create') }}"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-plus"></i> Новость
        </a>
    </div>

    {{-- 🧭 Фильтр --}}
    <div class="flex flex-wrap items-center gap-2 mb-6 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
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

        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Шаблоны:</span>

        <a href="{{ route('admin.news.index') }}"
            class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm
                  {{ !$currentTemplate ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            🗂️ Все
        </a>

        @foreach ($templates as $key => $label)
            <a href="{{ route('admin.news.index', array_merge(request()->except('category'), ['template' => $key])) }}"
                class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm
                      {{ $currentTemplate === $key ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $icons[$key] ?? '📄' }} {{ $label }}
            </a>
        @endforeach

        @if (count($categories))
            <select onchange="location = this.value"
                class="ml-auto border px-3 py-1.5 rounded text-sm text-gray-700 dark:text-gray-300 dark:bg-gray-800 shadow-sm">
                <option value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate])) }}">🗃️ Все
                    категории</option>
                @foreach ($categories as $cat)
                    <option
                        value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate, 'category' => $cat->id])) }}"
                        @if ($currentCategory == $cat->id) selected @endif>
                        🏷️ {{ $cat->title }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.news.bulk') }}" id="bulk-form">
        @csrf

        {{-- 🔘 Массовые действия --}}
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <select name="action"
                class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-black">
                <option value="">🔽 Действие</option>
                <option value="delete">🗑️ Удалить выбранные</option>
                <option value="edit">✏️ Массовое редактирование</option>
            </select>

            <button type="submit"
                class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-transform transform hover:scale-105">
                Применить
            </button>
        </div>

        {{-- 📊 Таблица --}}
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden shadow-md bg-white dark:bg-gray-900">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-3"><input type="checkbox" id="check-all"></th>
                        <th class="max-w-xs break-words">📝 Заголовок</th>
                        <th class="max-w-xs break-words">📂 Категории</th>
                        <th class="max-w-xs break-words">🔖 Meta Title</th>
                        <th class="max-w-sm break-words">🔑 Ключевые слова</th>
                        <th class="max-w-md break-words">📝 Meta Description</th>
                        <th>🛍️ Товар</th>
                        <th>📢 Статус</th>
                        <th>📦 Шаблон</th>
                        <th>⚙️</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($newsList as $news)
                        @php
                            $show = !$currentCategory || $news->categories->contains('id', $currentCategory);
                        @endphp
                        @if ($show)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="selected[]" value="{{ $news->id }}"
                                        class="row-checkbox">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200 break-words max-w-xs">
                                    {{ $news->title }}</td>
                                <td class="px-4 py-3 break-words max-w-xs">
                                    @foreach ($news->categories as $cat)
                                        <span
                                            class="inline-block bg-gray-200 text-gray-800 text-xs rounded-full px-2 py-0.5 mr-1 mb-1">
                                            🏷️ {{ $cat->title }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 break-words max-w-xs">
                                    {{ Str::limit($news->meta_title, 60) }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 break-words max-w-sm">
                                    {{ Str::limit($news->meta_keywords, 60) }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 break-words max-w-md">
                                    {{ Str::limit($news->meta_description, 100) }}</td>
                                <td class="px-4 py-3">
                                    @if ($news->template === 'products')
                                        💰 {{ number_format($news->price, 2, ',', ' ') }} ₽<br>
                                        📦 {{ $news->stock ?? 0 }} шт.<br>
                                        @if ($news->is_promo)
                                            <span
                                                class="inline-block mt-1 px-2 py-0.5 text-xs text-white bg-pink-500 rounded-full">🔥
                                                Акция</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-xl">
                                    {{ $news->published ? '✅' : '🕒' }}
                                </td>
                                <td class="px-4 py-3 text-center text-lg">
                                    {{ $icons[$news->template] ?? '📄' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.news.edit', $news->id) }}"
                                        class="text-blue-600 hover:text-blue-800 text-lg transition">
                                        ✏️
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 📄 Пагинация --}}
        <div class="mt-6">
            {{ $newsList->withQueryString()->onEachSide(1)->links('vendor.pagination.tailwind') }}
        </div>
    </form>

    {{-- 📜 Сценарии --}}
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
