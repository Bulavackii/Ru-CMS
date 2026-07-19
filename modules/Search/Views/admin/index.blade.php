@extends('layouts.admin')

@section('title', 'Поиск')
@section('header', 'Поиск по системе')

@section('content')
@php
    // ===== View helpers =====
    $query = (string) request('q', '');

    function highlight($text, $q) {
        $text = (string) $text;
        $q    = (string) $q;
        if ($q === '' || $text === '') {
            return e($text);
        }
        return preg_replace(
            '/' . preg_quote($q, '/') . '/iu',
            '<mark class="bg-yellow-200 text-black px-1 rounded">$0</mark>',
            e($text)
        );
    }

    // Счётчики (если коллекций может не быть — приводим к нулю)
    $cnt = [
        'modules'    => (int) ($modules->count()    ?? 0),
        'users'      => (int) ($users->count()      ?? 0),
        'categories' => (int) ($categories->count() ?? 0),
        'products'   => (int) ($products->count()   ?? 0),
        'news'       => (int) ($news->count()       ?? 0),
        'faq'        => (int) ($faq->count()        ?? 0),
        'reviews'    => (int) ($reviews->count()    ?? 0),
        'contacts'   => (int) ($contacts->count()   ?? 0),
    ];
    $totalFound = array_sum($cnt) + (int) ((isset($productsFromNews) && $productsFromNews)? $productsFromNews->count() : 0);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ========= ЛЕВАЯ ПАНЕЛЬ: Поиск, фильтры, подсказки ========= --}}
    <aside class="lg:col-span-1 lg:sticky lg:top-4 self-start">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow p-5"
             x-data="searchPanel()"
             x-init="init('{{ e($query) }}','{{ $filter }}','{{ $sort }}')">

            <div class="flex items-start justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-indigo-600"></i>
                    Умный поиск
                </h2>

                {{-- мини-хинт по горячим клавишам --}}
                <div class="hidden sm:flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="px-1 rounded bg-gray-100 dark:bg-gray-700">Ctrl</span>+
                    <span class="px-1 rounded bg-gray-100 dark:bg-gray-700">K</span>
                    <span class="ml-1">— фокус ввода</span>
                </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Ищите по названиям, описаниям и метаданным: модули, пользователи, товары, записи и другое.
            </p>

            {{-- Поле запроса --}}
            <label for="q" class="sr-only">Запрос</label>
            <div class="mt-4 relative">
                <input id="q" name="q" x-model.debounce.300ms="q"
                       @keydown.enter.prevent="goSearch()"
                       class="w-full border border-gray-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="Например: «товар акция», «email:@example.com», «FAQ оплата»">
                {{-- кнопки внутри поля --}}
                <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                    <button type="button" @click="q=''; $nextTick(()=> $refs.q?.focus())"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            x-show="q.length" x-transition
                            title="Очистить">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            {{-- Быстрые действия --}}
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" @click="goSearch()"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow transition">
                    <i class="fa-solid fa-magnifying-glass"></i> Искать
                </button>

                <button type="button" @click="copyLink()"
                        class="inline-flex items-center gap-2 border px-3 py-1.5 rounded-md text-sm shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                    <i class="fa-regular fa-copy"></i> Скопировать ссылку
                </button>

                <a href="{{ route('admin.search.index') }}"
                   class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300">
                    <i class="fa-solid fa-rotate"></i> Сбросить
                </a>
            </div>

            {{-- Чипы-фильтры с количеством --}}
            @php
                $chips = [
                    'modules'    => ['🧩','Модули'],
                    'users'      => ['👤','Пользователи'],
                    'categories' => ['🏷️','Категории'],
                    'products'   => ['🛒','Товары'],
                    'news'       => ['📰','Новости'],
                    'faq'        => ['❓','Вопросы'],
                    'reviews'    => ['💬','Отзывы'],
                    'contacts'   => ['📩','Контакты'],
                ];
            @endphp
            <div class="mt-4">
                <div class="text-[13px] text-gray-500 dark:text-gray-400 mb-1">Фильтр по разделам:</div>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button"
                            @click="setFilter('')"
                            class="chip"
                            :class="flt==='' ? 'chip--active' : ''">
                        <span>🔄 Все</span>
                    </button>

                    @foreach($chips as $key => [$icon,$label])
                        <button type="button"
                                @click="setFilter('{{ $key }}')"
                                class="chip"
                                :class="flt==='{{ $key }}' ? 'chip--active' : ''"
                                title="Найдено: {{ $cnt[$key] }}">
                            <span>{{ $icon }} {{ $label }}</span>
                            <span class="chip-badge">{{ $cnt[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Сортировка --}}
            <div class="mt-4">
                <label for="sort" class="text-[13px] text-gray-600 dark:text-gray-300">Сортировка:</label>
                <select id="sort" x-model="srt" @change="goSearch(true)"
                        class="mt-1 w-full border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 dark:text-gray-100">
                    <option value="relevance">По релевантности</option>
                    <option value="name_asc">По алфавиту (А-Я)</option>
                    <option value="name_desc">По алфавиту (Я-А)</option>
                    <option value="date_desc">Сначала новые</option>
                    <option value="date_asc">Сначала старые</option>
                </select>
            </div>

            {{-- Подсказки/пример запроса --}}
            <details class="mt-5 border border-gray-200 dark:border-gray-700 rounded-lg p-3 open:shadow-sm">
                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200">
                    📚 Подсказки и примеры
                </summary>
                <ul class="mt-2 space-y-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                    <li>• По нескольким словам: <code class="kbd">товар скидка</code></li>
                    <li>• По email: <code class="kbd">email:@example.com</code></li>
                    <li>• По разделу «Вопросы»: выберите фильтр «❓ Вопросы»</li>
                    <li>• По дате: отсортируйте «Сначала новые»</li>
                </ul>
            </details>

            {{-- Итог по результатам (если есть запрос) --}}
            @if($query !== '')
                <div class="mt-5 text-sm text-gray-600 dark:text-gray-300">
                    Найдено результатов: <span class="font-semibold">{{ number_format($totalFound, 0, ',', ' ') }}</span>
                </div>
            @endif
        </div>
    </aside>

    {{-- ========= ПРАВАЯ КОЛОНКА: Результаты ========= --}}
    <section class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow p-5 space-y-6">
            {{-- «пустой экран» до ввода запроса --}}
            @if($query === '')
                <div class="text-center py-12">
                    <div class="mx-auto w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 grid place-items-center mb-4">
                        <i class="fa-solid fa-search text-indigo-600 text-xl"></i>
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100">Начните поиск</div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Введите запрос слева — покажем совпадения по всем разделам.
                    </p>
                </div>
            @else
                {{-- «лента» результатов, сгруппированная по блокам --}}
                @foreach ([
                    [
                        'collection' => $modules ?? collect(),
                        'show'       => $showModules,
                        'icon'       => 'fas fa-puzzle-piece text-indigo-600',
                        'label'      => 'Модули',
                        'title'      => fn($item) => $item->name,
                        'desc'       => fn($item) => 'Версия: v'.$item->version,
                        'count'      => $cnt['modules'],
                    ],
                    [
                        'collection' => $users ?? collect(),
                        'show'       => $showUsers,
                        'icon'       => 'fas fa-user text-blue-600',
                        'label'      => 'Пользователи',
                        'title'      => fn($item) => $item->name,
                        'desc'       => fn($item) => 'Email: '.$item->email,
                        'count'      => $cnt['users'],
                    ],
                    [
                        'collection' => $categories ?? collect(),
                        'show'       => $showCategories,
                        'icon'       => 'fas fa-tag text-green-600',
                        'label'      => 'Категории',
                        'title'      => fn($item) => $item->title,
                        'desc'       => fn($item) => 'ID: '.$item->id,
                        'count'      => $cnt['categories'],
                    ],
                    [
                        'collection' => $products ?? collect(),
                        'show'       => $showProducts,
                        'icon'       => 'fas fa-box-open text-yellow-600',
                        'label'      => 'Товары',
                        'title'      => fn($item) => $item->name,
                        'desc'       => fn($item) => \Illuminate\Support\Str::limit(strip_tags((string)$item->description), 80),
                        'count'      => $cnt['products'],
                    ],
                    [
                        'collection' => $news ?? collect(),
                        'show'       => $showNews,
                        'icon'       => 'fas fa-newspaper text-cyan-600',
                        'label'      => 'Новости',
                        'title'      => fn($item) => $item->title,
                        'desc'       => fn($item) => '🗓 '.optional($item->created_at)->format('d.m.Y'),
                        'count'      => $cnt['news'],
                    ],
                    [
                        'collection' => $faq ?? collect(),
                        'show'       => $showFaq,
                        'icon'       => 'fas fa-question text-orange-600',
                        'label'      => 'Вопросы',
                        'title'      => fn($item) => $item->title,
                        'desc'       => fn($item) => \Illuminate\Support\Str::limit(strip_tags((string)$item->content), 80),
                        'count'      => $cnt['faq'],
                    ],
                    [
                        'collection' => $reviews ?? collect(),
                        'show'       => $showReviews,
                        'icon'       => 'fas fa-comment text-purple-600',
                        'label'      => 'Отзывы',
                        'title'      => fn($item) => $item->title,
                        'desc'       => fn($item) => \Illuminate\Support\Str::limit(strip_tags((string)$item->content), 80),
                        'count'      => $cnt['reviews'],
                    ],
                    [
                        'collection' => $contacts ?? collect(),
                        'show'       => $showContacts,
                        'icon'       => 'fas fa-envelope text-pink-600',
                        'label'      => 'Контакты',
                        'title'      => fn($item) => $item->subject ?? 'Без темы',
                        'desc'       => fn($item) => \Illuminate\Support\Str::limit(strip_tags((string)$item->body), 80),
                        'count'      => $cnt['contacts'],
                    ],
                ] as $block)
                    @if ($block['show'] && $block['collection']->count())
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    🔹 {{ $block['label'] }}
                                </h3>
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $block['count'] }}
                                </span>
                            </div>

                            @foreach ($block['collection'] as $item)
                                <x-admin-info-card :icon="$block['icon']">
                                    <x-slot name="title">{!! highlight($block['title']($item), $query) !!}</x-slot>
                                    {!! highlight($block['desc']($item), $query) !!}
                                </x-admin-info-card>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                {{-- Дополнительный блок: товары из новостей --}}
                @if (!empty($productsFromNews) && $productsFromNews->count())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">🛒 Товары из новостей</h3>
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ $productsFromNews->count() }}
                            </span>
                        </div>

                        @foreach ($productsFromNews as $item)
                            <x-admin-info-card icon="fas fa-box text-amber-600" :title="highlight($item->title, $query)">
                                {!! highlight(\Illuminate\Support\Str::limit(strip_tags((string)$item->content), 80), $query) !!}
                                <a href="{{ route('news.show', $item->slug ?? $item->id) }}" target="_blank"
                                   class="inline-block mt-2 text-xs text-blue-600 hover:underline">
                                    Открыть →
                                </a>
                            </x-admin-info-card>
                        @endforeach
                    </div>
                @endif

                {{-- Ничего не найдено --}}
                @if(
                    ($modules->count()    ?? 0) === 0 && $showModules &&
                    ($users->count()      ?? 0) === 0 && $showUsers &&
                    ($categories->count() ?? 0) === 0 && $showCategories &&
                    ($products->count()   ?? 0) === 0 && $showProducts &&
                    ($news->count()       ?? 0) === 0 && $showNews &&
                    ($faq->count()        ?? 0) === 0 && $showFaq &&
                    ($reviews->count()    ?? 0) === 0 && $showReviews &&
                    ($contacts->count()   ?? 0) === 0 && $showContacts &&
                    (empty($customResults) && $showCustom)
                )
                    <x-admin-info-card icon="fas fa-circle-question text-gray-400" title="Ничего не найдено">
                        Попробуйте упростить запрос, выбрать другой раздел или изменить сортировку.
                    </x-admin-info-card>
                @endif

                <p class="text-xs text-gray-400 text-right">
                    Время генерации: {{ round(microtime(true) - LARAVEL_START, 2) }} сек.
                </p>
            @endif
        </div>
    </section>
</div>

{{-- ===== Локальные стили для «чипов» и kbd ===== --}}
<style>
    .chip{
        @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs bg-white text-gray-700
               hover:bg-gray-50 shadow-sm dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:border-gray-700;
    }
    .chip--active{ @apply bg-black text-white ring-2 ring-indigo-500 border-black; }
    .chip-badge{ @apply text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700; }
    .kbd{ @apply px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100; }
</style>
@endsection

@push('scripts')
<script src="{{ local_js('alpine.min.js') }}" defer></script>
<script>
function searchPanel(){
    return {
        q: '',
        flt: '',
        srt: 'relevance',
        init(q, f, s){ this.q = q || ''; this.flt = f || ''; this.srt = s || 'relevance'; this.$nextTick(()=>{ this.$refs?.q?.focus?.(); }); },
        goSearch(replace=false){
            const url = new URL(@json(route('admin.search.index')), window.location.origin);
            if (this.q) url.searchParams.set('q', this.q); else url.searchParams.delete('q');
            if (this.flt) url.searchParams.set('filter', this.flt); else url.searchParams.delete('filter');
            if (this.srt) url.searchParams.set('sort', this.srt); else url.searchParams.delete('sort');
            replace ? window.location.replace(url) : window.location.href = url;
        },
        setFilter(f){ this.flt = f; this.goSearch(true); },
        copyLink(){
            const url = new URL(@json(route('admin.search.index')), window.location.origin);
            if (this.q) url.searchParams.set('q', this.q);
            if (this.flt) url.searchParams.set('filter', this.flt);
            if (this.srt) url.searchParams.set('sort', this.srt);
            navigator.clipboard?.writeText(url.href).then(()=>{
                const n = document.createElement('div');
                n.className='fixed bottom-4 left-1/2 -translate-x-1/2 bg-black text-white text-xs px-3 py-1.5 rounded shadow';
                n.textContent='Ссылка скопирована';
                document.body.appendChild(n);
                setTimeout(()=> n.remove(), 1100);
            });
        }
    }
}
</script>
@endpush
