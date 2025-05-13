@extends('layouts.admin')

@section('title', 'Поиск')
@section('header', 'Поиск по системе')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Левая колонка: форма поиска и фильтры --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-5">
                <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2 mb-4">
                    <i class="fas fa-search text-indigo-600"></i> Поиск по системе
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    Поиск по названиям, описаниям и метаданным — модули, пользователи, товары, записи и др.
                </p>

                <form method="GET" action="{{ route('admin.search.index') }}" x-data="{ q: '{{ request('q') }}' }"
                    class="flex flex-col gap-3">
                    <input type="text" name="q" x-model.debounce.500ms="q"
                        class="border border-gray-300 dark:border-gray-700 px-4 py-2 rounded-md text-sm w-full bg-white dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="🔎 Введите запрос...">
                    <div class="flex items-center gap-2">
                        <a :href="`{{ route('admin.search.index') }}?q=${encodeURIComponent(q)}`"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow transition w-fit">
                            <i class="fas fa-search"></i> Искать
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        @foreach ([
            'modules' => '🧩 Модули',
            'users' => '👤 Пользователи',
            'categories' => '🏷️ Категории',
            'products' => '🛒 Товары',
            'custom' => '🔌 Расширения',
            'news' => '📰 Новости',
            'faq' => '❓ Вопросы',
            'reviews' => '💬 Отзывы',
            'contacts' => '📩 Контакты',
        ] as $key => $label)
                            <a :href="`{{ route('admin.search.index') }}?q=${encodeURIComponent(q)}&filter={{ $key }}&sort={{ $sort }}`"
                                class="px-3 py-1 rounded-full text-sm font-medium border shadow-sm {{ $filter === $key ? 'bg-black text-white ring-2 ring-indigo-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                        <a :href="`{{ route('admin.search.index') }}?q=${encodeURIComponent(q)}&sort={{ $sort }}`"
                            class="px-3 py-1 rounded-full text-sm font-medium border shadow-sm {{ !$filter ? 'bg-black text-white ring-2 ring-indigo-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                            🔄 Все
                        </a>
                    </div>

                    <label for="sort" class="mt-4 text-sm text-gray-600 dark:text-gray-300">Сортировка:</label>
                    <div class="w-fit">
                        <select name="sort" id="sort"
                            class="w-fit border border-gray-300 dark:border-gray-700 px-3 py-1.5 rounded bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 text-sm"
                            onchange="location.href = '{{ route('admin.search.index') }}?q=' + encodeURIComponent(q) + '&filter={{ $filter }}&sort=' + this.value">
                            <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>По релевантности
                            </option>
                            <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>По алфавиту (А-Я)
                            </option>
                            <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>По алфавиту (Я-А)
                            </option>
                            <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Сначала новые</option>
                            <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Сначала старые</option>
                        </select>
                    </div>

                    <a href="{{ route('admin.search.index') }}"
                        class="text-xs text-gray-500 underline hover:text-indigo-600 mt-2">Очистить поиск</a>
                </form>
            </div>
        </div>

        {{-- Правая колонка: результаты поиска --}}
        <div
            class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-5 space-y-6">
            @php
                function highlight($text, $query)
                {
                    return preg_replace(
                        '/(' . preg_quote($query, '/') . ')/iu',
                        '<mark class="bg-yellow-200 text-black px-1 rounded">$1</mark>',
                        e($text),
                    );
                }
            @endphp

            @if ($query)
                @foreach ([
            ['collection' => $modules ?? collect(), 'show' => $showModules, 'icon' => 'fas fa-puzzle-piece text-indigo-600', 'title' => fn($item) => $item->name, 'desc' => fn($item) => 'Версия: v' . $item->version, 'label' => 'Модули'],
            ['collection' => $users ?? collect(), 'show' => $showUsers, 'icon' => 'fas fa-user text-blue-600', 'title' => fn($item) => $item->name, 'desc' => fn($item) => 'Email: ' . $item->email, 'label' => 'Пользователи'],
            ['collection' => $categories ?? collect(), 'show' => $showCategories, 'icon' => 'fas fa-tag text-green-600', 'title' => fn($item) => $item->title, 'desc' => fn($item) => 'ID: ' . $item->id, 'label' => 'Категории'],
            ['collection' => $products ?? collect(), 'show' => $showProducts, 'icon' => 'fas fa-box-open text-yellow-600', 'title' => fn($item) => $item->name, 'desc' => fn($item) => Str::limit(strip_tags($item->description), 80), 'label' => 'Товары'],
            ['collection' => $news ?? collect(), 'show' => $showNews, 'icon' => 'fas fa-newspaper text-cyan-600', 'title' => fn($item) => $item->title, 'desc' => fn($item) => '🗓 ' . optional($item->created_at)->format('d.m.Y'), 'label' => 'Новости'],
            ['collection' => $faq ?? collect(), 'show' => $showFaq, 'icon' => 'fas fa-question text-orange-600', 'title' => fn($item) => $item->title, 'desc' => fn($item) => Str::limit(strip_tags($item->content), 80), 'label' => 'Вопросы'],
            ['collection' => $reviews ?? collect(), 'show' => $showReviews, 'icon' => 'fas fa-comment text-purple-600', 'title' => fn($item) => $item->title, 'desc' => fn($item) => Str::limit(strip_tags($item->content), 80), 'label' => 'Отзывы'],
            ['collection' => $contacts ?? collect(), 'show' => $showContacts, 'icon' => 'fas fa-envelope text-pink-600', 'title' => fn($item) => $item->subject ?? 'Без темы', 'desc' => fn($item) => Str::limit(strip_tags($item->body), 80), 'label' => 'Контакты'],
        ] as $block)
                    @if ($block['show'] && $block['collection']->count())
                        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-2">🔹 {{ $block['label'] }}
                        </h3>
                        @foreach ($block['collection'] as $item)
                            <x-admin-info-card :icon="$block['icon']">
                                <x-slot name="title">{!! highlight($block['title']($item), $query) !!}</x-slot>
                                {!! highlight($block['desc']($item), $query) !!}
                            </x-admin-info-card>
                        @endforeach
                    @endif
                @endforeach

                {{-- Дополнительный блок: Товары из новостей (template = products) --}}
                @if (isset($productsFromNews) && $productsFromNews->count())
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-2">🛒 Товары из новостей</h3>
                    @foreach ($productsFromNews as $item)
                        <x-admin-info-card icon="fas fa-box text-amber-600" :title="highlight($item->title, $query)">
                            {!! highlight(Str::limit(strip_tags($item->content), 80), $query) !!}
                            <a href="{{ route('news.show', $item->slug ?? $item->id) }}" target="_blank"
                                class="inline-block mt-2 text-xs text-blue-600 hover:underline">
                                Посмотреть →
                            </a>
                        </x-admin-info-card>
                    @endforeach
                @endif

                @if (
                    !$modules->count() &&
                        $showModules &&
                        (!$users->count() && $showUsers) &&
                        (!$categories->count() && $showCategories) &&
                        (!$products->count() && $showProducts) &&
                        (!$news->count() && $showNews) &&
                        (!$faq->count() && $showFaq) &&
                        (!$reviews->count() && $showReviews) &&
                        (!$contacts->count() && $showContacts) &&
                        (empty($customResults) && $showCustom))
                    <x-admin-info-card icon="fas fa-question-circle text-gray-400" title="Ничего не найдено">
                        Попробуйте изменить запрос или снять фильтры.
                    </x-admin-info-card>
                @endif

                <p class="text-xs text-gray-400 text-right">
                    Время генерации: {{ round(microtime(true) - LARAVEL_START, 2) }} сек.
                </p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/alpinejs" defer></script>
@endpush
