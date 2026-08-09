@extends('layouts.frontend')

@section('title', $query !== '' ? __('frontend.search.title_with', ['query' => $query]) : __('frontend.search.title'))

@section('content')
@php
    use Illuminate\Support\Str;

    // Подсветка совпадений. Замыкание, а не глобальная функция: при повторном
    // рендере вьюхи в одном процессе объявление function уронило бы страницу.
    $highlight = function ($text, $q) {
        $text = e((string) $text);
        $q = trim((string) $q);

        if ($q === '' || $text === '') {
            return $text;
        }

        foreach (preg_split('/\s+/u', $q) as $word) {
            if (mb_strlen($word) < 2) {
                continue;
            }
            $text = preg_replace('/' . preg_quote(e($word), '/') . '/iu', '<mark class="fx-mark">$0</mark>', $text);
        }

        return $text;
    };

    // Выдержка вокруг совпадения: если слово нашлось в середине текста,
    // показывать надо именно этот фрагмент, а не первые 180 символов.
    $excerpt = function ($html, $q, $length = 200) {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)));
        $q = trim((string) $q);

        if ($text === '' || $q === '') {
            return Str::limit($text, $length);
        }

        $pos = mb_stripos($text, $q);

        if ($pos === false || $pos < 70) {
            return Str::limit($text, $length);
        }

        return '…' . Str::limit(mb_substr($text, $pos - 50), $length);
    };

    $short = mb_strlen($query) > 0 && mb_strlen($query) < 2;
@endphp

<div class="max-w-5xl mx-auto px-4 py-10">

    {{-- ── Шапка ── --}}
    <div class="flex items-center gap-3 mb-6">
        <span class="fx-badge"><i class="fas fa-magnifying-glass"></i></span>
        <div class="min-w-0">
            <h1 class="fx-section-title text-2xl sm:text-3xl">
                @if($query !== '')
                    {{ __('frontend.search.title') }}: <span class="fx-ico">{{ $query }}</span>
                @else
                    {{ __('frontend.search.title') }}
                @endif
            </h1>
            <p class="fx-section-sub mt-1">
                @if($query !== '' && !$short && $total > 0)
                    {{ __('frontend.search.found') }} <strong>{{ $total }}</strong>
                @else
                    {{ __('frontend.search.subtitle') }}
                @endif
            </p>
        </div>
    </div>

    {{-- Своей формы поиска тут нет: запрос уточняется полем в шапке сайта,
         дублировать его на странице результатов владелец попросил не надо --}}

    @if($short)
        {{-- ── Слишком короткий запрос ── --}}
        <div class="fx-card p-10 text-center">
            <span class="fx-badge mx-auto mb-4"><i class="fas fa-keyboard"></i></span>
            <h2 class="fx-section-title text-lg mb-1">{{ __('frontend.search.too_short') }}</h2>
            <p class="fx-section-sub">{{ __('frontend.search.too_short_hint') }}</p>
        </div>

    @elseif($query === '')
        {{-- ── Ещё ничего не искали ── --}}
        <div class="fx-card p-10 text-center">
            <span class="fx-badge mx-auto mb-4"><i class="fas fa-magnifying-glass"></i></span>
            <h2 class="fx-section-title text-lg mb-1">{{ __('frontend.search.start') }}</h2>
            <p class="fx-section-sub mb-6">{{ __('frontend.search.start_hint') }}</p>

            <a href="{{ route('news.index') }}" class="fx-btn px-5 py-2 text-sm">
                <i class="fas fa-newspaper"></i> {{ __('frontend.news.all') }}
            </a>
        </div>

    @elseif($total === 0)
        {{-- ── Ничего не найдено ── --}}
        <div class="fx-card p-10 text-center">
            <span class="fx-badge mx-auto mb-4"><i class="fas fa-circle-question"></i></span>
            <h2 class="fx-section-title text-lg mb-1">{{ __('frontend.search.nothing') }}</h2>
            <p class="fx-section-sub mb-6">
                {{ __('frontend.search.nothing_hint', ['query' => $query]) }}
            </p>

            <ul class="text-sm text-gray-600 space-y-1 mb-6 inline-block text-left">
                <li><i class="fas fa-check fx-ico mr-2"></i>{{ __('frontend.search.tip_spelling') }}</li>
                <li><i class="fas fa-check fx-ico mr-2"></i>{{ __('frontend.search.tip_one_word') }}</li>
                <li><i class="fas fa-check fx-ico mr-2"></i>{{ __('frontend.search.tip_general') }}</li>
            </ul>

            <div>
                <a href="{{ route('news.index') }}" class="fx-btn px-5 py-2 text-sm">
                    <i class="fas fa-newspaper"></i> {{ __('frontend.news.all') }}
                </a>
            </div>
        </div>

    @else
        {{-- ── Страницы сайта ── --}}
        @if($pages->isNotEmpty())
            <section class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="fx-section-title text-lg">{{ __('frontend.search.pages_section') }}</h2>
                    <span class="fx-chip">{{ $pages->count() }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($pages as $page)
                        <a href="{{ route('frontend.pages.show', $page->slug) }}"
                           class="fx-card p-4 flex items-start gap-3 no-underline">
                            <i class="fas fa-file-lines fx-ico mt-1"></i>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 break-words">
                                    {!! $highlight($page->title, $query) !!}
                                </span>
                                <span class="block text-sm text-gray-600 mt-1 break-words">
                                    {!! $highlight($excerpt($page->content, $query, 120), $query) !!}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Новости ── --}}
        @if($results->total() > 0)
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="fx-section-title text-lg">{{ __('frontend.news.section') }}</h2>
                    <span class="fx-chip">{{ $results->total() }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($results as $news)
                        @php
                            // Запись без slug открыть нечем: маршрут news/{slug} без него
                            // не строится и роняет всю страницу 500-й — показываем без ссылки
                            $newsUrl = $news->slug ? route('news.show', $news->slug) : null;
                        @endphp
                        <article class="fx-card p-5">
                            <h3 class="text-lg font-bold mb-2 leading-snug break-words">
                                @if($newsUrl)
                                    <a href="{{ $newsUrl }}"
                                       class="text-gray-900 hover:text-indigo-600 transition no-underline">
                                        {!! $highlight($news->title, $query) !!}
                                    </a>
                                @else
                                    <span class="text-gray-900">{!! $highlight($news->title, $query) !!}</span>
                                @endif
                            </h3>

                            <p class="text-sm text-gray-600 mb-3 break-words">
                                {!! $highlight($excerpt($news->content, $query), $query) !!}
                            </p>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <i class="far fa-calendar fx-ico"></i>
                                    {{ optional($news->created_at)->format('d.m.Y') }}
                                </span>

                                <span class="inline-flex items-center gap-1">
                                    <i class="far fa-clock fx-ico"></i>
                                    ~{{ __('frontend.news.reading_time', ['min' => reading_time($news->content)]) }}
                                </span>

                                @if($news->categories->isNotEmpty())
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fas fa-tag fx-ico"></i>
                                        {{ $news->categories->pluck('title')->implode(', ') }}
                                    </span>
                                @endif

                                @if($newsUrl)
                                    <a href="{{ $newsUrl }}"
                                       class="ml-auto text-indigo-600 font-medium no-underline hover:underline">
                                        {{ __('frontend.news.details') }} →
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $results->links('vendor.pagination.tailwind') }}
                </div>
            </section>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Подсветка совпадений — в тон акценту фронта */
    .fx-mark{ background:#fde68a; color:var(--surface-ink,#111827); padding:0 .12em; }
    :root.dark .fx-mark{ background:#a16207; color:#fff; }
</style>
@endpush
