@extends('layouts.admin')

@section('title', __('admin.search.title'))
@section('header', __('admin.search.heading'))

@section('content')
@php
    // Подсветка совпадений. Замыкание, а не глобальная функция: вьюха может
    // рендериться дважды за процесс, и повторное объявление function уронило бы её.
    $highlight = function ($text, $q) {
        $text = e((string) $text);
        $q = (string) $q;

        if ($q === '' || $text === '') {
            return $text;
        }

        return preg_replace('/' . preg_quote(e($q), '/') . '/iu', '<mark class="search-mark">$0</mark>', $text);
    };

    $visibleSections = collect($sections)->filter(fn ($s) => $s['visible'] && $s['items']->isNotEmpty());
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-magnifying-glass"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.search.heading') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.search.subtitle') }}
            </p>
        </div>
    </div>

    @if($query !== '')
        <div class="text-sm text-gray-600 dark:text-gray-300 flex-shrink-0">
            {{ __('admin.search.found') }}
            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($total, 0, ',', ' ') }}</span>
        </div>
    @endif
</div>

{{-- ── Панель запроса ── --}}
<form method="GET" action="{{ route('admin.search.index') }}" class="admin-card p-5 mb-5" id="searchForm">
    <div class="flex flex-col md:flex-row gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                 width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
            </svg>
            <input type="text" name="q" id="q" value="{{ $query }}" autofocus autocomplete="off"
                   placeholder="{{ __('admin.search.q_ph') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>

        <select name="sort" onchange="this.form.submit()"
                class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            <option value="relevance" @selected($sort === 'relevance')>{{ __('admin.search.sort_relevance') }}</option>
            <option value="name_asc"  @selected($sort === 'name_asc')>{{ __('admin.search.sort_name_asc') }}</option>
            <option value="name_desc" @selected($sort === 'name_desc')>{{ __('admin.search.sort_name_desc') }}</option>
            <option value="date_desc" @selected($sort === 'date_desc')>{{ __('admin.search.sort_date_desc') }}</option>
            <option value="date_asc"  @selected($sort === 'date_asc')>{{ __('admin.search.sort_date_asc') }}</option>
        </select>

        <button type="submit"
                class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                       px-5 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-magnifying-glass"></i> {{ __('admin.search.submit') }}
        </button>
    </div>

    {{-- Чипы разделов: каждый — обычная кнопка отправки формы, JS не нужен --}}
    <div class="mt-4 flex flex-wrap items-center gap-1.5">
        <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mr-1">{{ __('admin.search.section') }}</span>

        <button type="submit" name="filter" value=""
                class="search-chip {{ $filter === '' ? 'search-chip--active' : '' }}">
            <i class="fas fa-layer-group"></i> {{ __('admin.search.all') }}
            @if($query !== '')<span class="search-chip__badge">{{ $total }}</span>@endif
        </button>

        @foreach($sections as $key => $section)
            <button type="submit" name="filter" value="{{ $key }}"
                    class="search-chip {{ $filter === $key ? 'search-chip--active' : '' }} {{ $query !== '' && $section['count'] === 0 ? 'search-chip--empty' : '' }}">
                <i class="fas {{ $section['icon'] }}"></i> {{ $section['label'] }}
                @if($query !== '')<span class="search-chip__badge">{{ $section['count'] }}</span>@endif
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
        <button type="button" id="copyLink"
                class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition">
            <i class="fa-regular fa-copy"></i> {{ __('admin.search.copy_link') }}
        </button>
        @if($query !== '' || $filter !== '')
            <a href="{{ route('admin.search.index') }}"
               class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition">
                <i class="fas fa-rotate-left"></i> {{ __('admin.search.reset') }}
            </a>
        @endif
        <span class="text-gray-400 dark:text-gray-500">
            {{ __('admin.search.hint', ['n' => $perSection]) }}
        </span>
    </div>
</form>

{{-- ── Результаты ── --}}
@if($query === '')
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-magnifying-glass"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.search.start') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-5">
            {{ __('admin.search.start_hint') }}
        </p>

        <div class="text-left max-w-xl mx-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach([
                'admin@example.com' => __('admin.search.ex_email'),
                __('admin.search.ex_module_q') => __('admin.search.ex_module'),
                '+7' => __('admin.search.ex_phone'),
                __('admin.search.ex_page_q') => __('admin.search.ex_page'),
            ] as $example => $note)
                <a href="{{ route('admin.search.index', ['q' => $example]) }}"
                   class="border border-gray-200 dark:border-gray-700 p-3 hover:border-indigo-400 transition block">
                    <span class="font-mono text-sm text-indigo-600 dark:text-indigo-400">{{ $example }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $note }}</span>
                </a>
            @endforeach
        </div>
    </div>
@elseif($visibleSections->isEmpty() && empty($customResults))
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-circle-question"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.search.nothing') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ __('admin.search.nothing_hint', ['query' => $query, 'where' => $filter !== '' ? __('admin.search.in_section') : '']) }}
            {{ __('admin.search.nothing_tip') }}
        </p>
    </div>
@else
    <div class="space-y-5">
        @foreach($visibleSections as $key => $section)
            <div class="admin-card p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas {{ $section['icon'] }} text-indigo-500"></i> {{ $section['label'] }}
                    </h2>
                    <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5">
                        @if($section['count'] > $perSection)
                            {{ __('admin.search.shown', ['shown' => $section['items']->count(), 'total' => $section['count']]) }}
                        @else
                            {{ $section['count'] }}
                        @endif
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                    @foreach($section['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="search-result border border-gray-200 dark:border-gray-700 p-3 flex items-start gap-3 transition">
                            <i class="fas {{ $section['icon'] }} text-gray-400 mt-1"></i>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {!! $highlight($item['title'], $query) !!}
                                    </span>
                                    @if(!empty($item['badge']))
                                        <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-1.5 py-0.5">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </span>
                                @if(!empty($item['desc']))
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1 break-words">
                                        {!! $highlight($item['desc'], $query) !!}
                                    </span>
                                @endif
                            </span>
                            <i class="fas fa-arrow-right text-gray-300 mt-1"></i>
                        </a>
                    @endforeach
                </div>

                @if($section['count'] > $perSection && $filter !== $key)
                    <p class="admin-hint mt-3">
                        {{ __('admin.search.partial') }}
                        <a href="{{ route('admin.search.index', ['q' => $query, 'filter' => $key, 'sort' => $sort]) }}"
                           class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ __('admin.search.open_only', ['label' => $section['label']]) }}</a>
                    </p>
                @endif
            </div>
        @endforeach

        {{-- Результаты от модулей с собственным SearchProvider --}}
        @foreach($customResults as $moduleName => $results)
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-plug text-indigo-500"></i> {{ $moduleName }}
                </h2>
                <ul class="space-y-2">
                    @foreach($results as $result)
                        <li class="border border-gray-200 dark:border-gray-700 p-3 text-sm text-gray-700 dark:text-gray-300 break-words">
                            {!! $highlight(is_array($result) ? ($result['title'] ?? json_encode($result, JSON_UNESCAPED_UNICODE)) : $result, $query) !!}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни @-правил препроцессора,
       ни половины используемых здесь вариантов, поэтому чипы описаны обычными классами */
    .search-chip{
        display:inline-flex; align-items:center; gap:.375rem;
        padding:.3rem .6rem; font-size:.75rem; line-height:1;
        border:1px solid #d1d5db; background:#fff; color:#374151;
        transition:background-color .15s, border-color .15s, color .15s;
    }
    .search-chip:hover{ border-color:#6366f1; color:#4338ca; }
    .search-chip--active{ background:#4f46e5; border-color:#4f46e5; color:#fff; }
    .search-chip--active:hover{ background:#4338ca; border-color:#4338ca; color:#fff; }
    .search-chip--empty{ opacity:.55; }
    .search-chip__badge{
        font-size:.625rem; padding:.1rem .3rem;
        background:rgba(0,0,0,.06); color:inherit;
    }
    .search-chip--active .search-chip__badge{ background:rgba(255,255,255,.22); }

    .search-mark{ background:#fde68a; color:#111827; padding:0 .1em; }

    .search-result:hover{ border-color:#6366f1; background:#f8fafc; }
    .search-result:hover .fa-arrow-right{ color:#6366f1; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        // Ссылка на текущую выдачу — со всеми параметрами запроса
        const copy = document.getElementById('copyLink');
        if (copy) {
            copy.addEventListener('click', function () {
                navigator.clipboard?.writeText(window.location.href).then(() => {
                    const original = copy.innerHTML;
                    copy.innerHTML = '<i class="fas fa-check"></i> ' + @js(__('admin.search.js_copied'));
                    setTimeout(() => { copy.innerHTML = original; }, 1500);
                });
            });
        }

        // «/» ставит курсор в поле поиска (Ctrl+K занят глобальной палитрой в шапке)
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
            const tag = (document.activeElement?.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
            e.preventDefault();
            document.getElementById('q')?.focus();
        });
    })();
</script>
@endpush
