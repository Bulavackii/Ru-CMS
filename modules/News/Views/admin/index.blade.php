@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    @php
        $currentTemplate = request('template');
        $currentCategory = request('category');

        // Иконки шаблонов (FontAwesome вместо эмодзи — единый стиль с остальной админкой)
        $tplIcons = [
            'default'   => 'fa-newspaper',
            'products'  => 'fa-bag-shopping',
            'contacts'  => 'fa-address-card',
            'gallery'   => 'fa-images',
            'slideshow' => 'fa-film',
            'faq'       => 'fa-circle-question',
            'reviews'   => 'fa-star',
            'test'      => 'fa-flask',
            'test2'     => 'fa-gear',
        ];
    @endphp

    {{-- ── Шапка страницы: акцентная полоса + бейдж-иконка + действие ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-newspaper"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Новости</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Публикации, товары и другие материалы. Шаблоны отображения, категории и SEO-поля.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.news.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0"
           title="Создать новую публикацию">
            <i class="fas fa-plus"></i> Создать новость
        </a>
    </div>

    {{-- ── Подсказка ── --}}
    <div class="admin-hint px-4 py-3 mb-6 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 font-medium">
                <i class="fas fa-lightbulb"></i>
                <span>Шаблон определяет, как материал выводится на сайте. Только <b>опубликованные</b> записи видны посетителям.</span>
            </div>
            <div class="flex items-center gap-2 text-xs shrink-0">
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">Всего: {{ $newsList->total() }}</span>
            </div>
        </div>
    </div>

    {{-- ── Фильтры: шаблоны + категории ── --}}
    <div class="admin-card p-4 mb-5">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mr-1">Шаблоны</span>

                <a href="{{ route('admin.news.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition
                          {{ !$currentTemplate
                             ? 'bg-indigo-600 text-white shadow-sm'
                             : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-layer-group"></i> Все
                </a>

                @foreach ($templates as $key => $label)
                    <a href="{{ route('admin.news.index', array_merge(request()->except('category'), ['template' => $key])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition
                              {{ $currentTemplate === $key
                                 ? 'bg-indigo-600 text-white shadow-sm'
                                 : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700' }}">
                        <i class="fas {{ $tplIcons[$key] ?? 'fa-file-lines' }}"></i> {{ $label }}
                    </a>
                @endforeach
            </div>

            @if (count($categories))
                <div class="lg:ml-auto flex items-center gap-2 shrink-0">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Категория</span>
                    <select onchange="location = this.value"
                            class="border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-900
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate])) }}">Все категории</option>
                        @foreach ($categories as $cat)
                            <option value="{{ route('admin.news.index', array_filter(['template' => $currentTemplate, 'category' => $cat->id])) }}"
                                    @if ($currentCategory == $cat->id) selected @endif>
                                {{ $cat->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.news.bulk') }}" id="bulk-form">
        @csrf

        {{-- ── Массовые действия ── --}}
        <div class="admin-card p-3 mb-5 flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">С выбранными</span>

            <select name="action"
                    class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Выберите действие…</option>
                <option value="delete">Удалить выбранные</option>
                <option value="edit">Массовое редактирование</option>
            </select>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-check"></i> Применить
            </button>

            <span id="selected-counter" class="text-xs text-gray-500 dark:text-gray-400 ml-auto">Ничего не выбрано</span>
        </div>

        {{-- ── Таблица ── --}}
        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 w-10 text-left"><input type="checkbox" id="check-all" title="Выбрать все"></th>
                            <th class="px-4 py-3 text-left font-semibold">Заголовок</th>
                            <th class="px-4 py-3 text-left font-semibold">Категории</th>
                            <th class="px-4 py-3 text-left font-semibold">Meta Title</th>
                            <th class="px-4 py-3 text-left font-semibold">Ключевые слова</th>
                            <th class="px-4 py-3 text-left font-semibold">Meta Description</th>
                            <th class="px-4 py-3 text-left font-semibold">Товар</th>
                            <th class="px-4 py-3 text-center font-semibold">Статус</th>
                            <th class="px-4 py-3 text-center font-semibold">Шаблон</th>
                            <th class="px-4 py-3 text-center font-semibold w-16">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @php $rendered = 0; @endphp
                        @foreach ($newsList as $news)
                            @php $show = !$currentCategory || $news->categories->contains('id', $currentCategory); @endphp
                            @if ($show)
                                @php $rendered++; @endphp
                                <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-800 transition">
                                    <td class="px-4 py-3 align-top">
                                        <input type="checkbox" name="selected[]" value="{{ $news->id }}" class="row-checkbox">
                                    </td>

                                    <td class="px-4 py-3 align-top max-w-xs break-words">
                                        <a href="{{ route('admin.news.edit', $news->id) }}"
                                           class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                            {{ $news->title }}
                                        </a>
                                        <div class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-mono">ID {{ $news->id }}</div>
                                    </td>

                                    <td class="px-4 py-3 align-top max-w-xs break-words">
                                        @forelse ($news->categories as $cat)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 mr-1 mb-1 text-xs font-medium
                                                         bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                                {{ $cat->title }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endforelse
                                    </td>

                                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-300 break-words max-w-xs">
                                        {{ \Illuminate\Support\Str::limit($news->meta_title, 60) ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 align-top text-gray-500 dark:text-gray-400 break-words max-w-sm">
                                        {{ \Illuminate\Support\Str::limit($news->meta_keywords, 60) ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 align-top text-gray-500 dark:text-gray-400 break-words max-w-md">
                                        {{ \Illuminate\Support\Str::limit($news->meta_description, 100) ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 align-top whitespace-nowrap">
                                        @if ($news->template === 'products')
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ number_format($news->price, 2, ',', ' ') }} ₽
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <i class="fas fa-box"></i> {{ $news->stock ?? 0 }} шт.
                                            </div>
                                            @if ($news->is_promo)
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs font-semibold text-white bg-pink-500">
                                                    <i class="fas fa-fire"></i> Акция
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top text-center">
                                        @if ($news->published)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                                         bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300"
                                                  title="Видна посетителям">
                                                <i class="fas fa-circle-check"></i> Опубликовано
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                                         bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                                                  title="Скрыта от посетителей">
                                                <i class="fas fa-clock"></i> Черновик
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium whitespace-nowrap
                                                     bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                              title="Шаблон: {{ $templates[$news->template] ?? $news->template }}">
                                            <i class="fas {{ $tplIcons[$news->template] ?? 'fa-file-lines' }}"></i>
                                            {{ $templates[$news->template] ?? $news->template }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 align-top text-center">
                                        <a href="{{ route('admin.news.edit', $news->id) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                                           title="Редактировать">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach

                        @if ($rendered === 0)
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center">
                                    <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-newspaper"></i></span>
                                    <p class="text-gray-600 dark:text-gray-300 font-medium">Публикаций не найдено.</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Измените фильтры или
                                        <a href="{{ route('admin.news.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">создайте новость</a>.
                                    </p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Пагинация ── --}}
        <div class="mt-6">
            {{ $newsList->withQueryString()->onEachSide(1)->links('vendor.pagination.tailwind') }}
        </div>
    </form>

    {{-- ── Сценарии: «выбрать все», счётчик выбранных, валидация массовых действий ── --}}
    <script>
        const checkAll = document.getElementById('check-all');
        const rowBoxes = () => [...document.querySelectorAll('.row-checkbox')];
        const counter  = document.getElementById('selected-counter');

        // Русское склонение для счётчика выбранных записей
        function updateCounter() {
            const n = rowBoxes().filter(cb => cb.checked).length;
            if (!counter) return;
            if (!n) { counter.textContent = 'Ничего не выбрано'; return; }
            const m10 = n % 10, m100 = n % 100;
            const word = (m10 === 1 && m100 !== 11) ? 'запись'
                : ((m10 >= 2 && m10 <= 4 && !(m100 >= 12 && m100 <= 14)) ? 'записи' : 'записей');
            counter.textContent = `Выбрано: ${n} ${word}`;
        }

        checkAll?.addEventListener('change', e => {
            rowBoxes().forEach(cb => cb.checked = e.target.checked);
            updateCounter();
        });
        rowBoxes().forEach(cb => cb.addEventListener('change', () => {
            if (checkAll) checkAll.checked = rowBoxes().every(b => b.checked);
            updateCounter();
        }));
        updateCounter();

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

            if (action === 'delete' && !confirm(`Удалить выбранные записи (${selected.length})? Действие необратимо.`)) {
                e.preventDefault();
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
