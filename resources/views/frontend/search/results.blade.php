@extends('layouts.frontend')

@section('title', $query !== '' ? __('frontend.search.title_with', ['query' => $query]) : __('frontend.search.title'))

@section('content')
@php
    use Illuminate\Support\Str;

    // Подсветка совпадений. Замыкание, а не глобальная функция: при повторном
    // рендере вьюхи в одном процессе объявление function уронило бы страницу.
    //
    // Подсвечиваем то, ПО ЧЕМУ реально искали ($highlightTerms): при мягком
    // поиске это основы слов, и подсветка исходного запроса не совпала бы
    // ни с одной буквой.
    $highlight = function ($text, array $terms) {
        $text = e((string) $text);

        if ($text === '') {
            return $text;
        }

        foreach ($terms as $term) {
            foreach (preg_split('/\s+/u', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if (mb_strlen($word) < 2) {
                    continue;
                }
                $text = preg_replace('/' . preg_quote(e($word), '/') . '/iu', '<mark class="fx-mark">$0</mark>', $text);
            }
        }

        return $text;
    };

    // Выдержка вокруг совпадения: если слово нашлось в середине текста,
    // показывать надо именно этот фрагмент, а не первые 200 символов.
    // Ищем позицию ЛЮБОГО из слов — по целой фразе совпадения часто нет,
    // и выдержка молча съезжала на начало материала.
    $excerpt = function ($html, array $terms, $length = 200) {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)));

        if ($text === '') {
            return '';
        }

        $pos = false;

        foreach ($terms as $term) {
            foreach (preg_split('/\s+/u', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if (mb_strlen($word) < 2) {
                    continue;
                }
                $найдено = mb_stripos($text, $word);

                if ($найдено !== false && ($pos === false || $найдено < $pos)) {
                    $pos = $найдено;
                }
            }
        }

        if ($pos === false || $pos < 70) {
            return Str::limit($text, $length);
        }

        return '…' . Str::limit(mb_substr($text, $pos - 50), $length);
    };

    $поиск = fn ($слово) => route('frontend.search', ['q' => $слово]);
@endphp

<div class="max-w-screen-2xl mx-auto py-4">

    {{-- ── Шапка ──
         Одной строкой: знак, запрос и счётчик. Столбиком «заголовок +
         подпись под ним» она занимала вдвое больше по высоте ради двух
         коротких строк, а сама выдача уезжала вниз. --}}
    <div class="sr-head mb-5">
        <span class="fx-badge sr-head__ico">@themeIcon('search')</span>

        <h1 class="sr-head__title">
            @if($query !== '')
                {{ __('frontend.search.title') }}: <span class="fx-ico">{{ $query }}</span>
            @else
                {{ __('frontend.search.title') }}
            @endif
        </h1>

        <span class="sr-head__sub">
            @if($query !== '' && ! $short && $total > 0)
                {{ trans_choice('frontend.search.count', $total, ['count' => $total]) }}
            @else
                {{ __('frontend.search.subtitle') }}
            @endif
        </span>
    </div>

    {{-- Своей формы поиска тут нет: запрос уточняется полем в шапке сайта,
         дублировать его на странице результатов владелец попросил не надо --}}

    @if($approximate)
        {{-- Нашли не то, что просили дословно, — говорим об этом прямо,
             иначе выдача выглядит как ошибка поиска --}}
        <div class="sr-note mb-6">
            @themeIcon('info')
            <span>{{ __('frontend.search.approximate', ['terms' => implode(', ', $stems)]) }}</span>
        </div>
    @endif

    @if($short)
        {{-- ── Слишком короткий запрос ── --}}
        <div class="fx-card sr-empty sr-empty--solo">
            <div class="sr-empty__main">
                <span class="fx-badge sr-empty__ico">@themeIcon('edit')</span>
                <div class="min-w-0">
                    <h2 class="fx-section-title text-lg mb-1">{{ __('frontend.search.too_short') }}</h2>
                    <p class="fx-section-sub">{{ __('frontend.search.too_short_hint') }}</p>
                </div>
            </div>
        </div>

    @elseif($query === '')
        {{-- ── Ещё ничего не искали ── --}}
        <div class="fx-card sr-empty sr-empty--solo">
            <div class="sr-empty__main">
                <span class="fx-badge sr-empty__ico">@themeIcon('search')</span>
                <div class="min-w-0">
                    <h2 class="fx-section-title text-lg mb-1">{{ __('frontend.search.start') }}</h2>
                    <p class="fx-section-sub">{{ __('frontend.search.start_hint') }}</p>
                </div>
            </div>
        </div>

        @include('frontend.search.partials.latest', ['latest' => $latest])

    @elseif($total === 0)
        {{-- ── Ничего не найдено ──
             Кнопки «Все новости» тут больше нет: она уводила из поиска, а не
             помогала его уточнить. Вместо неё — готовые ссылки на поиск по
             отдельным словам и свежие материалы ниже.

             Раскладка в две колонки, а не столбиком по центру: карточка идёт
             во всю ширину содержимого, и по центру получалась узкая полоса
             текста с двумя полями пустоты по бокам. --}}
        <div class="fx-card sr-empty">
            <div class="sr-empty__main">
                <span class="fx-badge sr-empty__ico">@themeIcon('search')</span>
                <div class="min-w-0">
                    <h2 class="fx-section-title text-lg mb-1 break-words">
                        {{ __('frontend.search.nothing', ['query' => $query]) }}
                    </h2>
                    <p class="fx-section-sub">{{ __('frontend.search.nothing_hint') }}</p>

                    @if($hints !== [])
                        <p class="sr-tips__cap mt-5 mb-2">{{ __('frontend.search.try_words') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($hints as $слово)
                                <a href="{{ $поиск($слово) }}" class="sr-word">{{ $слово }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="sr-empty__aside">
                <p class="sr-tips__cap mb-2">{{ __('frontend.search.tips_cap') }}</p>
                <ul class="sr-tips">
                    <li>{{ __('frontend.search.tip_spelling') }}</li>
                    <li>{{ __('frontend.search.tip_one_word') }}</li>
                    <li>{{ __('frontend.search.tip_general') }}</li>
                </ul>
            </div>
        </div>

        @include('frontend.search.partials.latest', ['latest' => $latest])

    @else
        {{-- ── Страницы сайта ── --}}
        @if($pages->isNotEmpty())
            <section class="mb-8">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <h2 class="fx-section-title text-lg">{{ __('frontend.search.pages_section') }}</h2>
                    {{-- Число ВСЕХ совпавших страниц, а не показанных: раньше при
                         семи совпадениях блок уверенно сообщал «5» --}}
                    <span class="fx-chip">{{ $pagesTotal }}</span>
                    @if($pagesTotal > $pages->count())
                        <span class="sr-more">{{ __('frontend.search.pages_more', ['shown' => $pages->count()]) }}</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($pages as $page)
                        <a href="{{ route('frontend.pages.show', $page->slug) }}"
                           class="fx-card p-4 flex items-start gap-3 no-underline">
                            <span class="sr-ico">@themeIcon('file-text')</span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 break-words">
                                    {!! $highlight($page->title, $highlightTerms) !!}
                                </span>
                                <span class="block text-sm text-gray-600 mt-1 break-words">
                                    {!! $highlight($excerpt($page->content, $highlightTerms, 120), $highlightTerms) !!}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Материалы ── --}}
        @if($results->total() > 0)
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="fx-section-title text-lg">{{ __('frontend.search.materials_section') }}</h2>
                    <span class="fx-chip">{{ $results->total() }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($results as $news)
                        @php
                            // Запись без slug открыть нечем: маршрут news/{slug} без него
                            // не строится и роняет всю страницу 500-й — показываем без ссылки
                            $newsUrl = $news->slug ? route('news.show', $news->slug) : null;

                            // Товар и статья — разные вещи, и метка у них разная:
                            // «~1 мин чтения» у детского приёма в клинике выглядело нелепо.
                            $товар = $news->template === 'products';
                        @endphp
                        <article class="fx-card p-5">
                            <div class="flex flex-wrap items-start gap-x-3 gap-y-1 mb-2">
                                <h3 class="text-lg font-bold leading-snug break-words min-w-0 flex-1">
                                    @if($newsUrl)
                                        <a href="{{ $newsUrl }}"
                                           class="text-gray-900 hover:text-indigo-600 transition no-underline">
                                            {!! $highlight($news->title, $highlightTerms) !!}
                                        </a>
                                    @else
                                        <span class="text-gray-900">{!! $highlight($news->title, $highlightTerms) !!}</span>
                                    @endif
                                </h3>
                                <span class="sr-kind {{ $товар ? 'sr-kind--goods' : '' }}">
                                    {{ $товар ? __('frontend.search.type_product') : __('frontend.search.type_material') }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 mb-3 break-words">
                                {!! $highlight($excerpt($news->content, $highlightTerms), $highlightTerms) !!}
                            </p>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                @if($товар && $news->price !== null)
                                    <span class="sr-price">{{ number_format((float) $news->price, 0, ',', ' ') }} ₽</span>
                                @else
                                    <span class="inline-flex items-center gap-1">
                                        <span class="sr-ico">@themeIcon('clock')</span>
                                        ~{{ __('frontend.news.reading_time', ['min' => reading_time($news->content)]) }}
                                    </span>
                                @endif

                                <span class="inline-flex items-center gap-1">
                                    <span class="sr-ico">@themeIcon('calendar')</span>
                                    {{ optional($news->created_at)->format('d.m.Y') }}
                                </span>

                                @if($news->categories->isNotEmpty())
                                    <span class="inline-flex items-center gap-1">
                                        <span class="sr-ico">@themeIcon('tag')</span>
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
    /* Шапка страницы одной строкой. Селектор с body: у .fx-badge и .fx-section-*
       из макета такая же сила, и без этого размеры не перебивались. */
    .sr-head{ display:flex; align-items:center; flex-wrap:wrap; gap:.45rem .8rem; }
    body .sr-head__ico{
        flex:0 0 auto; margin:0; width:36px; height:36px; font-size:1rem;
    }
    body .sr-head__title{
        margin:0; font-weight:800; line-height:1.15; letter-spacing:-.02em;
        font-size:clamp(1.3rem, 2.2vw, 1.65rem);
        color:var(--surface-ink, #111827); overflow-wrap:anywhere;
    }
    .sr-head__sub{ color:var(--surface-mute, #6b7280); font-size:.875rem; }
    /* Точка-разделитель только когда счётчик стоит в той же строке */
    @media (min-width: 640px){
        .sr-head__sub::before{ content:'·'; margin-right:.55rem; opacity:.6; }
    }

    /* Подсветка совпадений — в тон акценту фронта */
    .fx-mark{ background:#fde68a; color:var(--surface-ink,#111827); padding:0 .12em; }
    :root.dark .fx-mark{ background:#a16207; color:#fff; }

    /* Плашка «показываем близкое»: сообщение о том, ЧТО именно нашлось,
       а не украшение — поэтому с рамкой, а не серой строкой мелким кеглем */
    .sr-note{
        display:flex; align-items:flex-start; gap:.6rem;
        padding:.7rem .9rem; font-size:.875rem; line-height:1.45;
        background:var(--surface-2, #f8fafc);
        border:1px solid var(--surface-line, #e2e8f0);
        border-left:3px solid var(--color-primary, #6366f1);
        color:var(--surface-ink, #111827);
    }
    .sr-note i, .sr-note svg{ color:var(--color-primary, #6366f1); flex:0 0 auto; margin-top:.1rem; }

    /* Слово запроса ссылкой: готовый поиск в один щелчок */
    .sr-word{
        display:inline-flex; align-items:center; min-height:32px;
        padding:.3rem .75rem; font-size:.875rem; font-weight:600;
        color:var(--color-primary, #6366f1); text-decoration:none;
        background:var(--surface-2, #f8fafc);
        border:1px solid var(--surface-line, #e2e8f0);
    }
    .sr-word:hover{ border-color:var(--color-primary, #6366f1); }

    /* Пустое состояние. Две колонки: слева — что случилось и что нажать,
       справа — что попробовать. Карточка идёт во всю ширину содержимого,
       и столбик по центру оставлял два поля пустоты по бокам. */
    .sr-empty{
        display:grid; grid-template-columns:minmax(0, 1.6fr) minmax(0, 1fr);
        gap:2rem; align-items:start; padding:1.75rem 2rem;
    }
    .sr-empty--solo{ grid-template-columns:1fr; }
    .sr-empty__main{ display:flex; align-items:flex-start; gap:1rem; }
    /* fx-badge в остальных местах центрируется через mx-auto — здесь он
       стоит в строке и не должен ни растягиваться, ни съезжать */
    .sr-empty__ico{ flex:0 0 auto; margin:0; }
    .sr-empty__aside{
        padding-left:2rem;
        border-left:1px solid var(--surface-line, #e2e8f0);
    }

    /* Советы. Прежние зелёные галочки читались как «уже сделано» —
       это подсказки, а не отметки о выполнении */
    .sr-tips{
        list-style:none; padding-left:0; margin:0; text-align:left;
        font-size:.875rem; color:var(--surface-mute, #6b7280);
    }
    .sr-tips li{ padding:.25rem 0 .25rem 1rem; position:relative; }
    .sr-tips li::before{
        content:''; position:absolute; left:0; top:.85em;
        width:5px; height:5px; background:var(--color-primary, #6366f1);
    }
    .sr-tips__cap{
        font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:var(--surface-mute, #6b7280);
    }

    .sr-more{ font-size:.75rem; color:var(--surface-mute, #6b7280); }

    /* Метка вида материала */
    .sr-kind{
        flex:0 0 auto;
        font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
        padding:.2rem .5rem; color:var(--surface-mute, #6b7280);
        background:var(--surface-2, #f8fafc);
        border:1px solid var(--surface-line, #e2e8f0);
    }
    .sr-kind--goods{
        color:var(--color-primary, #6366f1);
        border-color:var(--color-primary, #6366f1);
    }

    .sr-price{ font-weight:700; font-size:.9rem; font-variant-numeric:tabular-nums;
               color:var(--surface-ink, #111827); }

    /* line-height наследуется в плитку значка, и глиф оседает вниз */
    .sr-ico{ display:inline-flex; align-items:center; line-height:1; color:var(--color-primary, #6366f1); }

    /* Порог проекта: мельче 12px браузер на телефоне предлагает увеличить страницу */
    @media (max-width: 1024px), (max-height: 500px){
        body .sr-kind, body .sr-more, body .sr-tips__cap{ font-size:12px; }

        /* Двух колонок тут не хватит по ширине — разделитель переезжает наверх */
        body .sr-empty{ grid-template-columns:1fr; gap:1.25rem; padding:1.25rem; }
        body .sr-empty__aside{
            padding-left:0; padding-top:1.25rem;
            border-left:0; border-top:1px solid var(--surface-line, #e2e8f0);
        }
    }
</style>
@endpush
