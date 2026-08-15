{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  📚 ОБЩИЙ ПАРТИАЛ ШАБЛОНОВ «УРОКИ»                               ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Четыре шаблона уроков (HTML, CSS, JavaScript, PHP) были         ║
    ║  ЧЕТЫРЬМЯ КОПИЯМИ одной разметки, отличавшимися цветом и          ║
    ║  подписью. Копии уже начали расходиться: блок подсветки кода      ║
    ║  остался только у HTML, остальные три её потеряли.                ║
    ║                                                                  ║
    ║  ПАРАМЕТРЫ                                                       ║
    ║    $lang   — подпись языка на плашке («HTML», «PHP»)              ║
    ║    $accent — фирменный цвет языка                                 ║
    ║    $ink    — цвет надписи поверх акцента                          ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → «Шаблон» = base-html и т.п.      ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    $lessonList = $newsList ?? ($templates[$template ?? ''] ?? collect());

    // Строк кода в материале — по ним видно, урок это с примерами или
    // теория. Считаем <pre>, а не <code>: инлайновый код есть в любом тексте.
    $codeBlocks = fn ($html) => preg_match_all('~<pre[\s>]~i', (string) $html);
@endphp

@if ($lessonList->count())
<section class="ls" style="--ls-accent: {{ $accent }}; --ls-ink: {{ $ink }}">
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-code"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.templates.' . ($shortKey ?? 'html')) }}</h2>
            <p class="fx-section-sub">{{ __('frontend.lessons.subtitle') }}</p>
        </div>
    </div>

    <div class="ls-grid">
        @foreach ($lessonList as $lesson)
            @php $blocks = $codeBlocks($lesson->content); @endphp

            <article class="ls-card">
                <div class="ls-card__head">
                    <span class="ls-card__lang">{{ $lang }}</span>

                    @if ($blocks)
                        {{-- Число примеров — единственное, что отличает уроки
                             друг от друга при беглом просмотре списка. --}}
                        <span class="ls-card__code" title="{{ __('frontend.lessons.examples') }}">
                            <i class="fas fa-terminal" aria-hidden="true"></i>{{ $blocks }}
                        </span>
                    @endif
                </div>

                <h3 class="ls-card__title">
                    <a href="{{ route('news.show', $lesson->slug) }}">{{ $lesson->title }}</a>
                </h3>

                <p class="ls-card__text">{{ content_excerpt($lesson->content, 170) }}</p>

                <div class="ls-card__foot">
                    <time class="ls-card__date" datetime="{{ $lesson->created_at?->toDateString() }}">
                        {{ $lesson->created_at?->format('d.m.Y') }}
                    </time>
                    <span class="ls-card__time">{{ reading_time($lesson->content) }}</span>

                    <a href="{{ route('news.show', $lesson->slug) }}" class="ls-card__more">
                        {{ __('frontend.news.read_full') }} →
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    {{-- ⚠️ method_exists: на главной список приходит обычной коллекцией. --}}
    @if (method_exists($lessonList, 'hasPages') && $lessonList->hasPages())
        <div class="ls-pager">{{ $lessonList->links() }}</div>
    @endif
</section>
@endif

@once
@push('styles')
<style>
    /* Литеральный CSS: в сборке нет ни line-clamp, ни произвольных значений,
       ни прозрачности через дробь. Прежние четыре копии были собраны как раз
       из них, плюс цвета зашиты литералами — на тёмных темах заголовок
       пропадал, а заглушка no-image занимала 192 пикселя в каждой карточке.

       Обёртка «только один раз» вокруг блока стилей обязательна: партиал
       подключают четыре шаблона, и на главной со всеми четырьмя разделами
       стили ушли бы в ответ четырежды.
       ВНИМАНИЕ: имя этой директивы нельзя писать здесь буквально — Blade
       компилирует директивы даже внутри CSS-комментария (см. CLAUDE.md). */
    .ls{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    /* Та же сетка, что у «Товаров», «Отзывов» и «Услуг». */
    .ls-grid{ display:grid;
        grid-template-columns:repeat(auto-fill, minmax(min(100%,19rem), 1fr));
        gap:1rem }

    .ls-card{ display:flex; flex-direction:column; gap:.55rem;
        padding:1.1rem 1.2rem 1rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7);
        border-top:3px solid var(--ls-accent);
        transition:transform .18s ease, box-shadow .18s ease }
    .ls-card:hover{ transform:translateY(-3px);
        box-shadow:0 18px 40px -26px color-mix(in srgb, var(--ls-accent) 60%, rgba(15,23,42,.5)) }
    .ls-card :focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    .ls-card__head{ display:flex; align-items:center; justify-content:space-between; gap:.6rem }

    /* Язык моноширинным капсом: он и есть главный признак урока. */
    .ls-card__lang{ font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.7rem; font-weight:800; letter-spacing:.08em;
        padding:.18rem .5rem; color:var(--ls-ink); background:var(--ls-accent) }

    .ls-card__code{ display:inline-flex; align-items:center; gap:.3rem;
        font-size:.72rem; font-weight:700; font-variant-numeric:tabular-nums;
        color:var(--surface-mute,#64748b) }

    .ls-card__title{ margin:.15rem 0 0; font-size:1.04rem; line-height:1.35; font-weight:700 }
    .ls-card__title a{ color:var(--surface-ink,#111827); text-decoration:none;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .ls-card__title a:hover{ color:color-mix(in srgb, var(--ls-accent) 72%, var(--surface-ink,#111827)) }

    .ls-card__text{ margin:0; font-size:.87rem; line-height:1.55; flex:1;
        color:var(--surface-mute,#64748b);
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }

    .ls-card__foot{ display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding-top:.65rem; border-top:1px solid var(--surface-bd,#f1f5f9);
        font-size:.75rem; color:var(--surface-dim,#94a3b8);
        font-variant-numeric:tabular-nums }

    /* Ссылка прижата к правому краю — как во всех остальных разделах.
       ⚠️ margin-left:auto на inline-flex не работает, нужен flex. */
    .ls-card__more{ display:flex; align-items:center; margin-left:auto;
        font-size:.84rem; font-weight:700;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827)) }

    .ls-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы (разбор в
       CLAUDE.md), поэтому prefers-color-scheme здесь нет. */
    body.fx-theme-dark .ls-card{ background:var(--surface); border-color:var(--surface-bd);
        border-top-color:var(--ls-accent) }
    body.fx-theme-dark .ls-card__title a{ color:var(--surface-ink) }

    @media (max-width: 1024px), (max-height: 500px){
        .ls{ margin:1.5rem auto; padding:0 .75rem }
        .ls-grid{ gap:.75rem }
        .ls-card{ padding:1rem }
        .ls-card__foot{ font-size:12px }
        .ls-card__more{ min-height:32px }
    }

    @media (prefers-reduced-motion: reduce){
        .ls-card{ transition:none }
        .ls-card:hover{ transform:none }
    }
</style>
@endpush
@endonce
