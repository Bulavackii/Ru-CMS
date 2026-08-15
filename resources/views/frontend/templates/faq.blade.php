{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  ❓ ШАБЛОН «ВОПРОСЫ»                                             ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Задача раздела одна: быстро НАЙТИ нужный ответ. Поэтому здесь   ║
    ║  список вопросов, а не сетка карточек с обложками — картинка     ║
    ║  искать не помогает, а каждая занимала 192 пикселя высоты.       ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = faq              ║
    ║    Заголовок материала — это вопрос, текст — ответ.               ║
    ║                                                                  ║
    ║  РАСКРЫТИЕ                                                       ║
    ║    Родные <details>/<summary>: работают без единой строки JS,     ║
    ║    открываются поиском по странице (Ctrl+F находит текст даже в   ║
    ║    закрытом блоке) и доступны с клавиатуры сами по себе.          ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Сначала $newsList — его отдаёт контроллер, сгруппировав материалы по
    // шаблонам. $templates['faq'] — переменная из прежнего устройства
    // главной, её больше нет.
    $faqList = $newsList ?? ($templates['faq'] ?? collect());
@endphp

@if ($faqList->count())
<section class="fq">
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-circle-question"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.templates.faq') }}</h2>
            <p class="fx-section-sub">{{ __('frontend.faq.subtitle') }}</p>
        </div>
    </div>

    <div class="fq-list">
        @foreach ($faqList as $faq)
            {{-- Первый вопрос открыт: пустой список из одних заголовков не
                 объясняет, что внутри, и по нему не понятно, что он
                 раскрывается. --}}
            {{-- name у <details> связывает их в один набор: открытие одного
                 закрывает остальные. Без него раскрывались все сразу, список
                 растягивался на несколько экранов и место терялось — а раздел
                 существует ради быстрого поиска ответа.

                 Атрибут родной, без JS. В браузере, который его ещё не знает,
                 поведение прежнее (открываются несколько) — ничего не
                 ломается, просто список длиннее. --}}
            <details class="fq-item" name="fq-{{ $loop->parent->index ?? 0 }}"
                     @if ($loop->first) open @endif>
                <summary class="fq-q">
                    <span class="fq-q__text">{{ $faq->title }}</span>
                    <i class="fas fa-chevron-down fq-q__arrow" aria-hidden="true"></i>
                </summary>

                <div class="fq-a">
                    @if ($faq->categories->count())
                        <span class="fq-rubric">{{ $faq->categories->first()->title }}</span>
                    @endif

                    <p class="fq-a__text">{{ content_excerpt($faq->content, 320) }}</p>

                    <a href="{{ route('news.show', $faq->slug) }}" class="fq-a__more">
                        {{ __('frontend.news.read_full') }} →
                    </a>
                </div>
            </details>
        @endforeach
    </div>

    {{-- ⚠️ method_exists: на главной список приходит обычной коллекцией
         (материалы уже сгруппированы по шаблонам), и hasPages() там нет. --}}
    @if (method_exists($faqList, 'hasPages') && $faqList->hasPages())
        <div class="fq-pager">{{ $faqList->links() }}</div>
    @endif
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет ни line-clamp, ни
       произвольных значений, ни прозрачности через дробь. Прежняя версия
       была собрана как раз из таких утилит, а бейдж «❓ FAQ» дёргался на
       animate-pulse без остановки. */
    /* Ширина как у остальных разделов (80rem): при 56rem блок заметно уже
       соседних «Отзывов» и «Товаров», и на широком экране это читалось как
       ошибка вёрстки, а не как замысел. */
    .fq{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .fq-list{ display:flex; flex-direction:column;
        border:1px solid var(--surface-bd,#eef2f7); background:var(--surface,#fff) }

    .fq-item + .fq-item{ border-top:1px solid var(--surface-bd,#eef2f7) }

    /* Вопрос. Указатель мыши и заметная зона нажатия: строка целиком —
       орган управления, а не просто текст. */
    .fq-q{ display:flex; align-items:center; gap:.85rem; cursor:pointer;
        padding:.95rem 1.15rem; min-height:52px;
        font-size:1rem; font-weight:600; line-height:1.4;
        color:var(--surface-ink,#111827); list-style:none;
        transition:background .15s ease }
    /* Родной треугольник убираем — вместо него своя стрелка справа. */
    .fq-q::-webkit-details-marker{ display:none }
    .fq-q::marker{ content:'' }
    .fq-q:hover{ background:color-mix(in srgb, var(--color-primary,#6366f1) 5%, transparent) }
    .fq-q:focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:-2px }

    .fq-q__text{ flex:1 }
    .fq-q__arrow{ flex:none; font-size:.8rem; opacity:.55;
        color:var(--color-primary,#6366f1); transition:transform .2s ease }
    .fq-item[open] .fq-q__arrow{ transform:rotate(180deg) }
    .fq-item[open] .fq-q{ color:var(--color-primary,#6366f1) }

    /* Ответ отбит подложкой и полосой слева: видно, к какому вопросу он
       относится, даже когда открыто несколько. */
    /* ⚠️ Зазор сверху такой же, как снизу и по бокам. Раньше сверху стоял
       ноль: панель ответа прилегала к строке вопроса вплотную при 16
       пикселях с трёх остальных сторон — замер это и показал, на глаз
       читалось как «слиплось». */
    .fq-a{ padding:.55rem 1.15rem 1.05rem 1.15rem;
        border-left:3px solid color-mix(in srgb, var(--color-primary,#6366f1) 45%, transparent);
        margin:0 1.15rem 1rem; background:var(--surface-2,#f8fafc) }
    .fq-item[open] .fq-a{ margin-top:.25rem }
    /* Мера строки ограничена ВНУТРИ широкого блока: ответ читают, а не
       просматривают, и строка через весь экран утомляет. Так раздел
       выровнен с соседними, а текст остаётся удобным. */
    .fq-a__text{ margin:.7rem 0 0; max-width:70ch; font-size:.95rem; line-height:1.65;
        color:var(--surface-ink,#475569) }
    /* Ссылка у ПРАВОГО края — там же, где «Читать целиком» в карточках
       новостей и журнала: движение вперёд по всему сайту в одном месте.

       ⚠️ display:flex + width:max-content, а не inline-flex: у inline-flex
       margin-left:auto не работает вовсе — это уже ловилось в подвале. */
    .fq-a__more{ display:flex; align-items:center; width:max-content;
        margin:.6rem 0 0 auto; min-height:32px;
        font-size:.88rem; font-weight:700; color:var(--color-primary,#6366f1) }

    .fq-rubric{ display:inline-block; margin-top:.7rem;
        font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.66rem; letter-spacing:.08em; text-transform:uppercase;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 78%, var(--surface-ink,#111827)) }

    .fq-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы. Блока
       @media (prefers-color-scheme: dark) здесь нет намеренно: это
       настройка ОС, и при тёмной системе со светлым сайтом раздел уезжал
       бы в тёмный посреди светлой страницы (разбор — в CLAUDE.md). */
    body.fx-theme-dark .fq-list{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .fq-item + .fq-item{ border-top-color:var(--surface-bd) }
    body.fx-theme-dark .fq-a{ background:var(--surface-2) }

    @media (max-width: 1024px), (max-height: 500px){
        .fq{ margin:1.5rem auto; padding:0 .75rem }
        /* Вопрос — основная зона нажатия раздела, поэтому 52 растёт до 56:
           попасть по нему нужно с первого раза. */
        .fq-q{ min-height:56px; padding:.9rem 1rem; font-size:.95rem }
        .fq-a{ margin:0 1rem 1rem; padding:.2rem 1rem 1rem }
        .fq-a__text{ font-size:.92rem }
    }

    /* Движение отключается по настройке «уменьшить анимацию». */
    @media (prefers-reduced-motion: reduce){
        .fq-q, .fq-q__arrow{ transition:none }
    }
</style>
@endpush
