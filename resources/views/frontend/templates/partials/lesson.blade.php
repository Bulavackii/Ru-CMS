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
    ║    $lang   — подпись языка («HTML», «PHP»)                        ║
    ║    $icon   — значок языка из брендового набора Font Awesome       ║
    ║    $accent — фирменный цвет языка                                 ║
    ║    $ink    — цвет надписи поверх акцента                          ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → «Шаблон» = base-html и т.п.      ║
    ║                                                                  ║
    ║  ЧТО БЕРЁТСЯ ИЗ САМОГО МАТЕРИАЛА                                 ║
    ║    примеры кода  — число блоков <pre> в тексте                    ║
    ║    время чтения  — реальный подсчёт слов (см. reading_time)        ║
    ║    порядковый №  — СКВОЗНОЙ номер материала, а не позиция в ленте ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    $lessonList = $newsList ?? ($templates[$template ?? ''] ?? collect());

    // Примеры кода — блоки <pre>, а не <code>: инлайновый код есть в любом
    // тексте, и считать его значило бы показывать «12 примеров» у теории.
    $codeBlocks = fn ($html) => preg_match_all('~<pre[\s>]~i', (string) $html);

    // Сквозные номера уроков. НЕ позиция в списке: список идёт свежим вперёд,
    // и новый урок получал бы номер 01, сдвигая нумерацию всем остальным.
    $numbers = template_numbers($template ?? 'base-html');
@endphp

@if ($lessonList->count())
<section class="ls" style="--ls-accent: {{ $accent }}; --ls-ink: {{ $ink }}">
    <div class="fx-section-head">
        {{-- Значок раздела — брендовый глиф языка, а не общая «решётка кода»:
             четыре раздела уроков подряд иначе неразличимы. --}}
        <span class="fx-badge ls-badge"><i class="fab {{ $icon }}"></i></span>
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
                    <span class="ls-card__icon" aria-hidden="true">
                        <i class="fab {{ $icon }}"></i>
                    </span>

                    <span class="ls-card__lang">{{ $lang }}</span>

                    {{-- Номер урока СКВОЗНОЙ: заведён пятым — значит пятый,
                         где бы он ни оказался в списке. Приглушён, чтобы не
                         спорить с заголовком. --}}
                    <span class="ls-card__num" aria-hidden="true">{{ sprintf('%02d', $numbers[$lesson->id] ?? $loop->iteration) }}</span>
                </div>

                <h3 class="ls-card__title">
                    <a href="{{ route('news.show', $lesson->slug) }}">{{ $lesson->title }}</a>
                </h3>

                <p class="ls-card__text">{{ content_excerpt($lesson->content, 165) }}</p>

                <div class="ls-card__meta">
                    @if ($blocks)
                        <span class="ls-card__chip" title="{{ __('frontend.lessons.examples') }}">
                            <i class="fas fa-terminal" aria-hidden="true"></i>{{ $blocks }}
                        </span>
                    @endif

                    <span class="ls-card__chip">
                        <i class="far fa-clock" aria-hidden="true"></i>{{ reading_time($lesson->content) }}
                    </span>

                    <time class="ls-card__date" datetime="{{ $lesson->created_at?->toDateString() }}">
                        {{ $lesson->created_at?->format('d.m.Y') }}
                    </time>
                </div>

                <a href="{{ route('news.show', $lesson->slug) }}" class="ls-card__more">
                    {{ __('frontend.lessons.open') }} <span aria-hidden="true">→</span>
                </a>
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

    /* Значок раздела в цвете языка. Плашка общая (.fx-badge), меняется
       только заливка — чтобы четыре раздела уроков отличались с первого
       взгляда, но не выпадали из общего ряда разделов сайта. */
    .ls-badge{ background:var(--ls-accent) !important; color:var(--ls-ink) !important }

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

    .ls-card__head{ display:flex; align-items:center; gap:.55rem }

    /* Квадрат со значком языка. Обложки у урока нет и быть не должно:
       снимок ничего не сообщает о содержимом, а места занимает 192 пикселя. */
    .ls-card__icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2.4rem; height:2.4rem; flex:none; font-size:1.25rem; line-height:1;
        color:var(--ls-ink); background:var(--ls-accent) }

    .ls-card__lang{ font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.72rem; font-weight:800; letter-spacing:.09em;
        color:color-mix(in srgb, var(--ls-accent) 68%, var(--surface-ink,#111827)) }

    .ls-card__num{ margin-left:auto;
        font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:1.35rem; font-weight:800; line-height:1;
        color:color-mix(in srgb, var(--ls-accent) 24%, transparent) }

    .ls-card__title{ margin:.2rem 0 0; font-size:1.04rem; line-height:1.35; font-weight:700 }
    .ls-card__title a{ color:var(--surface-ink,#111827); text-decoration:none;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .ls-card__title a:hover{ color:color-mix(in srgb, var(--ls-accent) 72%, var(--surface-ink,#111827)) }

    .ls-card__text{ margin:0; font-size:.87rem; line-height:1.55; flex:1;
        color:var(--surface-mute,#64748b);
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }

    .ls-card__meta{ display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
        padding-top:.6rem; border-top:1px solid var(--surface-bd,#f1f5f9);
        font-size:.74rem; color:var(--surface-dim,#94a3b8);
        font-variant-numeric:tabular-nums }
    .ls-card__chip{ display:inline-flex; align-items:center; gap:.3rem;
        font-weight:700; color:var(--surface-mute,#64748b) }
    .ls-card__date{ margin-left:auto }

    /* Ссылка во всю ширину: у урока одно действие — открыть его. Кнопкой
       она не сделана намеренно, разделы сайта не соревнуются за внимание. */
    .ls-card__more{ display:flex; align-items:center; justify-content:space-between;
        margin-top:.15rem; min-height:32px; font-size:.86rem; font-weight:700;
        color:color-mix(in srgb, var(--ls-accent) 62%, var(--surface-ink,#111827)) }

    .ls-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы (разбор в
       CLAUDE.md), поэтому prefers-color-scheme здесь нет. */
    body.fx-theme-dark .ls-card{ background:var(--surface); border-color:var(--surface-bd);
        border-top-color:var(--ls-accent) }
    body.fx-theme-dark .ls-card__title a{ color:var(--surface-ink) }
    body.fx-theme-dark .ls-card__lang,
    body.fx-theme-dark .ls-card__more{ color:color-mix(in srgb, var(--ls-accent) 62%, var(--surface-ink)) }

    @media (max-width: 1024px), (max-height: 500px){
        .ls{ margin:1.5rem auto; padding:0 .75rem }
        .ls-grid{ gap:.75rem }
        .ls-card{ padding:1rem }
        .ls-card__icon{ width:2.2rem; height:2.2rem; font-size:1.1rem }
        .ls-card__meta{ font-size:12px }
        .ls-card__more{ min-height:36px }
    }

    @media (prefers-reduced-motion: reduce){
        .ls-card{ transition:none }
        .ls-card:hover{ transform:none }
    }
</style>
@endpush
@endonce
