{{--
    Свежие материалы под пустой выдачей.

    Раньше на этом месте стояла одна кнопка «Все новости»: она уводила из
    поиска в список из семи десятков записей и ничем не помогала. Три
    свежих материала — такой же выход из тупика, но по ним видно, о чём
    сайт вообще, и переход осмысленный.
--}}
@php
    use Illuminate\Support\Str;

    // Выдержка нужна, иначе на широком экране карточка — это полоса
    // с заголовком и полем пустоты в половину её высоты.
    $выдержка = fn ($html) => Str::limit(
        trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html))),
        110,
    );
@endphp

@if($latest->isNotEmpty())
    <section class="mt-8">
        <div class="flex items-center gap-3 mb-3">
            <h2 class="fx-section-title text-lg">{{ __('frontend.search.latest') }}</h2>
            <a href="{{ route('news.index') }}" class="sr-all">
                {{ __('frontend.search.latest_all') }} →
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($latest as $материал)
                <a href="{{ route('news.show', $материал->slug) }}" class="fx-card sr-fresh no-underline">
                    <span class="sr-fresh__ico">@themeIcon('file-text')</span>

                    <span class="sr-fresh__body">
                        <span class="sr-fresh__title">{{ $материал->title }}</span>

                        @if($текст = $выдержка($материал->content))
                            <span class="sr-fresh__text">{{ $текст }}</span>
                        @endif

                        <span class="sr-fresh__foot">
                            <span class="sr-fresh__date">
                                {{ optional($материал->created_at)->format('d.m.Y') }}
                            </span>
                            <span class="sr-fresh__go">{{ __('frontend.news.read_more') }} →</span>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@push('styles')
<style>
    /* Ссылка «все» стоит в одной строке с заголовком раздела и прижата вправо */
    .sr-all{
        margin-left:auto; font-size:.875rem; font-weight:600; text-decoration:none;
        color:var(--color-primary, #6366f1);
    }
    .sr-all:hover{ text-decoration:underline; }

    /* Карточка материала: значок слева, содержимое колонкой.
       Высота выровнена по ряду (align-items:stretch у сетки), поэтому
       нижняя строка прижата к низу — иначе даты в ряду висят вразнобой. */
    .sr-fresh{
        display:flex; align-items:flex-start; gap:.85rem;
        padding:1.1rem 1.15rem; height:100%;
    }
    .sr-fresh__ico{
        display:inline-flex; align-items:center; justify-content:center;
        flex:0 0 auto; width:34px; height:34px; line-height:1; font-size:1rem;
        color:var(--color-primary, #6366f1);
        background:var(--surface-2, #f8fafc);
        border:1px solid var(--surface-line, #e2e8f0);
    }
    .sr-fresh__body{ display:flex; flex-direction:column; flex:1 1 auto; min-width:0; }
    .sr-fresh__title{
        font-weight:700; line-height:1.3; color:var(--surface-ink, #111827);
        overflow-wrap:break-word;
    }
    .sr-fresh__text{
        margin-top:.35rem; font-size:.875rem; line-height:1.5;
        color:var(--surface-mute, #6b7280); overflow-wrap:break-word;
    }
    .sr-fresh__foot{
        display:flex; align-items:center; gap:.75rem;
        margin-top:auto; padding-top:.75rem; font-size:.75rem;
    }
    .sr-fresh__date{ color:var(--surface-mute, #6b7280); font-variant-numeric:tabular-nums; }
    .sr-fresh__go{ margin-left:auto; font-weight:600; color:var(--color-primary, #6366f1); }
    .sr-fresh:hover .sr-fresh__go{ text-decoration:underline; }

    /* Порог проекта: мельче 12px браузер на телефоне предлагает увеличить страницу */
    @media (max-width: 1024px), (max-height: 500px){
        body .sr-fresh__foot{ font-size:12px; }
    }
</style>
@endpush
