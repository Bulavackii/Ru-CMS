{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  💬 ШАБЛОН «ОТЗЫВЫ»                                              ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Задача раздела — чужой опыт, которому верят. Значит на виду     ║
    ║  должны быть три вещи: сам текст, оценка и когда это было.        ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = reviews          ║
    ║                                                                  ║
    ║  ОЦЕНКА                                                          ║
    ║    Поле «Оценка» в форме материала, шкала 0..10 — ОДНА на весь    ║
    ║    проект (её же носят «Игры»). В карточке рисуется пятью         ║
    ║    звёздами, где звезда равна двум баллам, и числом рядом.        ║
    ║    Раньше поле сохранялось только у «Игр», поэтому у отзыва оно   ║
    ║    всегда оставалось пустым и блок оценки не появлялся ВООБЩЕ.    ║
    ║                                                                  ║
    ║  ⚠️ АВТОРА ОТЗЫВА В БАЗЕ НЕТ                                     ║
    ║    Прежняя версия печатала «👤 Аноним» у каждой карточки: она     ║
    ║    читала $review->author, а такой колонки не существует —        ║
    ║    подпись была всегда одна и та же и ничего не значила. Пока     ║
    ║    поля нет, имя не выдумываем: заголовок материала и есть        ║
    ║    строка отзыва.                                                 ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Сначала $newsList — его отдаёт контроллер, сгруппировав материалы по
    // шаблонам. $templates['reviews'] — переменная из прежнего устройства
    // главной, её больше нет.
    $reviewsList = $newsList ?? ($templates['reviews'] ?? collect());

    // Первая картинка из текста. Заглушку НЕ подставляем: у отзыва фото
    // бывает редко, а пустая рамка с «нет изображения» только занимала
    // 192 пикселя в каждой карточке.
    $coverOf = function ($item) {
        if (! empty($item->cover)) {
            return asset('storage/' . ltrim((string) $item->cover, '/'));
        }

        return preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', (string) $item->content, $m)
            ? $m[1]
            : null;
    };
@endphp

@if ($reviewsList->count())
<section class="rv">
    {{-- Общая плашка раздела: свой класс .rv__head в набор макета не входил,
         и заголовок оставался вообще без оформления. --}}
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-comments"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.reviews.title') }}</h2>
            <p class="fx-section-sub">{{ __('frontend.reviews.subtitle') }}</p>
        </div>
    </div>

    <div class="rv-grid">
        @foreach ($reviewsList as $review)
            @php
                $cover = $coverOf($review);

                // Оценка 0..10 → пять звёзд, звезда равна двум баллам.
                $score = (! is_null($review->rating) && $review->rating > 0)
                    ? round((float) $review->rating, 1)
                    : null;
                $stars = $score !== null ? (int) round($score / 2) : 0;
            @endphp

            <article class="rv-card">
                {{-- Кавычка — знак прямой речи. Она же отделяет отзыв от
                     обычной заметки при беглом просмотре страницы. --}}
                <span class="rv-card__quote" aria-hidden="true">“</span>

                <h3 class="rv-card__title">
                    <a href="{{ route('news.show', $review->slug) }}">{{ $review->title }}</a>
                </h3>

                <p class="rv-card__text">{{ content_excerpt($review->content, 190) }}</p>

                @if ($cover)
                    {{-- Фото показываем, только если оно РЕАЛЬНО есть: снимок
                         работы подтверждает отзыв, а заглушка — нет. --}}
                    <a href="{{ route('news.show', $review->slug) }}" class="rv-card__media">
                        <img src="{{ $cover }}" alt="{{ $review->title }}" loading="lazy">
                    </a>
                @endif

                <div class="rv-card__foot">
                    @if ($score !== null)
                        <span class="rv-card__score"
                              title="{{ __('frontend.reviews.score', ['score' => number_format($score, 1, ',', '')]) }}"
                              aria-label="{{ __('frontend.reviews.score', ['score' => number_format($score, 1, ',', '')]) }}">
                            <span class="rv-stars" aria-hidden="true">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $stars ? 'is-on' : '' }}"></i>
                                @endfor
                            </span>
                            <b>{{ number_format($score, 1, ',', '') }}</b>
                        </span>
                    @endif

                    <time class="rv-card__date" datetime="{{ $review->created_at?->toDateString() }}">
                        {{ $review->created_at?->format('d.m.Y') }}
                    </time>
                </div>
            </article>
        @endforeach
    </div>

    {{-- ⚠️ method_exists: на главной список приходит обычной коллекцией
         (материалы уже сгруппированы по шаблонам), и hasPages() там нет. --}}
    @if (method_exists($reviewsList, 'hasPages') && $reviewsList->hasPages())
        <div class="rv-pager">{{ $reviewsList->links() }}</div>
    @endif
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет ни line-clamp, ни
       произвольных значений, ни прозрачности через дробь. Прежняя версия
       шаблона была собрана как раз из таких утилит — половина из них не
       работала (line-clamp-4, max-w-[60%]), а мигающий бейдж на
       animate-pulse дёргался без остановки. */
    .rv{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    /* Заполнение — как в «Товарах»: те же гибкие дорожки, тот же минимум,
       карточки занимают строку целиком. Так два раздела на одной странице
       выглядят одним набором, а не двумя разными сетками.

       min(100%, …) обязателен: без него дорожка в 19rem не влезает в 360 с
       полями и вся страница получает горизонтальную прокрутку. */
    .rv-grid{ display:grid;
        grid-template-columns:repeat(auto-fill, minmax(min(100%,19rem), 1fr));
        gap:1rem }

    .rv-card{ position:relative; display:flex; flex-direction:column; gap:.55rem;
        padding:1.6rem 1.35rem 1.15rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7);
        transition:border-color .18s ease, transform .18s ease, box-shadow .18s ease }
    .rv-card:hover{ border-color:color-mix(in srgb, var(--color-accent,#8b5cf6) 45%, var(--surface-bd,#eef2f7));
        transform:translateY(-3px);
        box-shadow:0 18px 40px -26px color-mix(in srgb, var(--color-accent,#8b5cf6) 60%, rgba(15,23,42,.5)) }
    .rv-card :focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    /* Кавычка приглушена и заходит за край текста: она оформление, а не
       содержимое, и не должна спорить с первой строкой. */
    .rv-card__quote{ position:absolute; top:.1rem; left:.75rem;
        font-size:3.4rem; line-height:1; font-weight:800;
        color:var(--color-accent,#8b5cf6); opacity:.18; pointer-events:none }

    .rv-card__title{ margin:0; font-size:1.02rem; line-height:1.35; font-weight:700 }
    .rv-card__title a{ color:var(--surface-ink,#111827); text-decoration:none;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .rv-card__title a:hover{ color:var(--color-primary,#6366f1) }

    /* Сам отзыв — главное в карточке, поэтому строк ему отведено больше,
       чем анонсу в других шаблонах. */
    .rv-card__text{ margin:0; font-size:.88rem; line-height:1.6; flex:1;
        color:var(--surface-mute,#64748b);
        display:-webkit-box; -webkit-line-clamp:5; -webkit-box-orient:vertical; overflow:hidden }

    .rv-card__media{ display:block; width:100%; aspect-ratio:4 / 3; overflow:hidden;
        background:var(--surface-2,#f1f5f9) }
    .rv-card__media img{ width:100%; height:100%; object-fit:cover; display:block }

    .rv-card__foot{ display:flex; align-items:center; justify-content:space-between;
        gap:.75rem; flex-wrap:wrap; padding-top:.6rem;
        border-top:1px solid var(--surface-bd,#f1f5f9) }

    /* Оценка: пять звёзд плюс число. Звёзды видно издалека, число снимает
       вопрос «сколько именно» — одного без другого мало. */
    .rv-card__score{ display:inline-flex; align-items:center; gap:.4rem;
        font-size:.85rem; font-variant-numeric:tabular-nums }
    .rv-stars{ display:inline-flex; gap:.08rem; font-size:.78rem;
        color:color-mix(in srgb, var(--surface-dim,#94a3b8) 60%, transparent) }
    .rv-stars .is-on{ color:#f59e0b }
    .rv-card__score b{ font-weight:800; color:var(--surface-ink,#111827) }

    .rv-card__date{ font-size:.75rem; color:var(--surface-dim,#94a3b8);
        font-variant-numeric:tabular-nums; white-space:nowrap }

    .rv-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы. Блока
       @media (prefers-color-scheme: dark) здесь нет намеренно: это
       настройка ОС, и при тёмной системе со светлым сайтом раздел уезжал
       бы в тёмный посреди светлой страницы (разбор — в CLAUDE.md). */
    body.fx-theme-dark .rv-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .rv__title,
    body.fx-theme-dark .rv-card__title a{ color:var(--surface-ink) }

    @media (max-width: 1024px), (max-height: 500px){
        .rv{ margin:1.5rem auto; padding:0 .75rem }
        .rv-grid{ gap:.75rem }
        .rv-card{ padding:1.4rem 1.1rem 1rem }
        .rv-card__date{ font-size:12px }
        .rv-card__title a{ -webkit-line-clamp:3 }
    }
</style>
@endpush
