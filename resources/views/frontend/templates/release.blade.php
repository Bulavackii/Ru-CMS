{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🚀 ШАБЛОН «РЕЛИЗЫ»                                              ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Раздел отвечает на два вопроса: ЧТО изменилось и КОГДА.          ║
    ║  Поэтому здесь лента по времени, а не сетка карточек с            ║
    ║  обложками: картинка у записи об обновлении ничего не сообщает,   ║
    ║  а занимала 192 пикселя высоты в каждой.                          ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = release          ║
    ║                                                                  ║
    ║  НОМЕР ВЕРСИИ                                                    ║
    ║    Берётся из заголовка: «Версия 1.1.0» → плашка «1.1.0», а в     ║
    ║    строке остаётся «Версия». Номера нет — плашки нет, заголовок   ║
    ║    показывается целиком.                                          ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Сначала $newsList — его отдаёт контроллер, сгруппировав материалы по
    // шаблонам. $templates['release'] — переменная из прежнего устройства
    // главной, её больше нет.
    $releaseList = $newsList ?? ($releaseList ?? ($templates['release'] ?? collect()));

    // Порядок задаёт контроллер (created_at, потом id) — сортировать здесь
    // нельзя: шаблону приходит ПАГИНАТОР, а не коллекция, и сортировка
    // затронула бы только текущую страницу.

    // Номер версии из заголовка: 1.1, 1.1.0, 2.0.3-beta.
    $splitVersion = function (string $title): array {
        if (! preg_match('~(\d+(?:\.\d+)+(?:-[\w.]+)?)~u', $title, $m)) {
            return ['version' => null, 'label' => $title];
        }

        // ⚠️ Двойной пробел на месте вырезанного номера обязателен к чистке:
        // «Версия 1.0.0 — первый выпуск» давало «Версия  — первый выпуск».
        $label = trim(preg_replace('~\s+~u', ' ', str_replace($m[1], '', $title)), " \t—–-·:");

        // Если от заголовка осталось одно слово («Версия»), он перестаёт
        // что-либо сообщать — тогда показываем исходный целиком, а плашка с
        // номером остаётся быстрым ориентиром.
        $осмысленно = preg_match_all('~[\p{L}\p{N}]+~u', $label) > 1;

        return ['version' => $m[1], 'label' => $осмысленно ? $label : $title];
    };
@endphp

@if ($releaseList->count())
<section class="rl">
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-rocket"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.templates.release') }}</h2>
            <p class="fx-section-sub">{{ __('frontend.release.subtitle') }}</p>
        </div>
    </div>

    <ol class="rl-line">
        @foreach ($releaseList as $release)
            @php $parts = $splitVersion((string) $release->title); @endphp

            <li class="rl-item">
                {{-- Точка на линии времени. Самая свежая запись выделена:
                     в списке обновлений первым делом ищут последнее. --}}
                <span class="rl-dot {{ $loop->first ? 'is-latest' : '' }}" aria-hidden="true"></span>

                <div class="rl-body">
                    <div class="rl-top">
                        @if ($parts['version'])
                            <span class="rl-ver">{{ $parts['version'] }}</span>
                        @endif

                        <time class="rl-date" datetime="{{ $release->created_at?->toDateString() }}">
                            {{ $release->created_at?->format('d.m.Y') }}
                        </time>

                        @if ($loop->first)
                            <span class="rl-fresh">{{ __('frontend.release.latest') }}</span>
                        @endif
                    </div>

                    <h3 class="rl-title">
                        <a href="{{ route('news.show', $release->slug) }}">
                            {{ $parts['label'] ?? $release->title }}
                        </a>
                    </h3>

                    <p class="rl-text">{{ content_excerpt($release->content, 260) }}</p>

                    <a href="{{ route('news.show', $release->slug) }}" class="rl-more">
                        {{ __('frontend.news.read_full') }} →
                    </a>
                </div>
            </li>
        @endforeach
    </ol>

    {{-- ⚠️ method_exists: на главной список приходит обычной коллекцией
         (материалы уже сгруппированы по шаблонам), и hasPages() там нет. --}}
    @if (method_exists($releaseList, 'hasPages') && $releaseList->hasPages())
        <div class="rl-pager">{{ $releaseList->links() }}</div>
    @endif
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет ни произвольных
       значений, ни прозрачности через дробь. Прежняя версия шаблона была
       собрана как раз из таких утилит. */
    .rl{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    /* Лента времени. Вертикальная линия — это и есть история: по ней видно,
       что записи идут одна за другой, а не лежат россыпью. */
    .rl-line{ position:relative; margin:0; padding:0 0 0 1.75rem; list-style:none }
    .rl-line::before{ content:''; position:absolute; left:.42rem; top:.35rem; bottom:.35rem;
        width:2px; background:linear-gradient(to bottom,
            color-mix(in srgb, var(--color-primary,#6366f1) 55%, transparent),
            color-mix(in srgb, var(--color-primary,#6366f1) 12%, transparent)) }

    .rl-item{ position:relative; padding:0 0 1.35rem }
    .rl-item:last-child{ padding-bottom:0 }

    .rl-dot{ position:absolute; left:-1.53rem; top:.42rem;
        width:.75rem; height:.75rem; border-radius:50%;
        background:var(--surface,#fff);
        border:2px solid color-mix(in srgb, var(--color-primary,#6366f1) 45%, var(--surface-bd,#e2e8f0)) }
    /* Свежая запись: в списке обновлений её ищут первой. */
    .rl-dot.is-latest{ background:var(--color-primary,#6366f1);
        border-color:var(--color-primary,#6366f1);
        box-shadow:0 0 0 4px color-mix(in srgb, var(--color-primary,#6366f1) 18%, transparent) }

    .rl-body{ padding:.85rem 1.1rem 1rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7);
        transition:border-color .18s ease, box-shadow .18s ease }
    .rl-item:hover .rl-body{ border-color:color-mix(in srgb, var(--color-primary,#6366f1) 40%, var(--surface-bd,#eef2f7));
        box-shadow:0 14px 34px -26px color-mix(in srgb, var(--color-primary,#6366f1) 60%, rgba(15,23,42,.5)) }
    .rl-body :focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    .rl-top{ display:flex; align-items:center; flex-wrap:wrap; gap:.5rem }

    /* Номер версии моноширинным: столбец номеров читается сверху вниз, а
       цифры не пляшут по разрядам. */
    .rl-ver{ font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.8rem; font-weight:700; letter-spacing:.01em;
        padding:.14rem .5rem; color:var(--on-accent,#fff);
        background:linear-gradient(135deg, var(--color-primary,#6366f1), var(--color-accent,#8b5cf6)) }

    .rl-date{ font-size:.76rem; color:var(--surface-dim,#94a3b8);
        font-variant-numeric:tabular-nums }

    .rl-fresh{ font-size:.66rem; font-weight:800; letter-spacing:.06em;
        text-transform:uppercase; padding:.14rem .45rem;
        color:color-mix(in srgb, #15803d 80%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #22c55e 16%, var(--surface,#f0fdf4));
        border:1px solid color-mix(in srgb, #22c55e 34%, var(--surface-bd,#bbf7d0)) }

    .rl-title{ margin:.45rem 0 0; font-size:1.05rem; line-height:1.35; font-weight:700 }
    .rl-title a{ color:var(--surface-ink,#111827); text-decoration:none }
    .rl-title a:hover{ color:var(--color-primary,#6366f1) }

    /* Мера строки ограничена внутри широкого блока: список изменений
       читают, а строка через весь экран утомляет. */
    .rl-text{ margin:.4rem 0 0; max-width:70ch; font-size:.9rem; line-height:1.6;
        color:var(--surface-mute,#64748b) }

    /* Ссылка у правого края — как в остальных разделах сайта.
       ⚠️ display:flex + width:max-content: у inline-flex margin-left:auto
       не работает вовсе. */
    .rl-more{ display:flex; align-items:center; width:max-content;
        margin:.55rem 0 0 auto; min-height:32px;
        font-size:.86rem; font-weight:700; color:var(--color-primary,#6366f1) }

    .rl-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы. Блока
       @media (prefers-color-scheme: dark) здесь нет намеренно (разбор —
       в CLAUDE.md). */
    body.fx-theme-dark .rl-body{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .rl-dot{ background:var(--surface) }
    body.fx-theme-dark .rl-title a{ color:var(--surface-ink) }

    @media (max-width: 1024px), (max-height: 500px){
        .rl{ margin:1.5rem auto; padding:0 .75rem }
        .rl-line{ padding-left:1.5rem }
        .rl-dot{ left:-1.28rem }
        .rl-body{ padding:.8rem .9rem .9rem }
        .rl-date{ font-size:12px }
        .rl-text{ font-size:.9rem }
    }
</style>
@endpush
