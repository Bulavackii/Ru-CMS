{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🎮 ШАБЛОН «ИГРЫ»                                                ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Для игровых сообществ: анонсы, обзоры, патчноуты, турниры.       ║
    ║  Карточки тёмные поверх светлого фона — привычный игровым         ║
    ║  сайтам вид, но при этом шаблон живёт на любой теме проекта.      ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = gaming           ║
    ║                                                                  ║
    ║  КАК ЗАДАТЬ МЕТКУ И ОЦЕНКУ                                       ║
    ║    Метка — первый эмодзи заголовка: «🔥 Патч 1.4» → значок огня.  ║
    ║    Оценка — поле «Оценка» в форме материала: 0..10, плашкой в     ║
    ║    углу обложки. Пусто — плашки нет.                              ║
    ║                                                                  ║
    ║  ОБЛОЖКА                                                         ║
    ║    Первый тег <img> в тексте. Нет картинки — блок заливается      ║
    ║    градиентом темы, поэтому дыр в сетке не будет.                 ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    $splitIcon = function ($title) {
        $title = trim((string) $title);

        if (preg_match('~^(\X)\s+(.+)$~u', $title, $m) && ! preg_match('~^[\p{L}\p{N}]~u', $m[1])) {
            return ['icon' => $m[1], 'text' => $m[2]];
        }

        return ['icon' => '🎮', 'text' => $title];
    };

    $coverOf = function ($item) {
        return preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', (string) $item->content, $m)
            ? $m[1]
            : null;
    };

    // ⚠️ Через content_excerpt, а не strip_tags. Тот убирает `</p><p>` не
    // оставляя пробела, и конец абзаца прирастал к началу следующего:
    // «…отвечаем на вопросы в чате.Запись остаётся в архиве».
    $excerptOf = fn ($item, int $limit = 130) => content_excerpt($item->content, $limit);

    $items = $newsList ?? collect();
@endphp

@if ($items->count())
<section class="gm">
    <div class="gm__head">
        <span class="gm__badge">🎮</span>
        <div>
            <h2 class="gm__title">{{ $title ?? __('frontend.gaming.title') }}</h2>
            <p class="gm__sub">{{ __('frontend.gaming.subtitle') }}</p>
        </div>
    </div>

    <div class="gm-grid">
        @foreach ($items as $item)
            @php
                $parts = $splitIcon($item->title);
                $cover = $coverOf($item);
                // Оценка — собственное поле материала. Раньше она бралась из
                // «Цены», и обзор игры превращался в товар за 8,50 ₽ с кнопкой
                // «В корзину». Оценка и цена — разные вещи.
                // Всегда с одним знаком после точки: «9» показывается как
                // «9.0». Так плашка одинаковой ширины у всех карточек, а
                // оценка читается как оценка, а не как порядковый номер.
                $score = (!is_null($item->rating) && $item->rating > 0)
                    ? number_format((float) $item->rating, 1, '.', '')
                    : null;
            @endphp

            <a href="{{ url('/news/' . $item->slug) }}" class="gm-card">
                <div class="gm-card__media">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="{{ $parts['text'] }}" loading="lazy">
                    @endif

                    <span class="gm-card__tag" aria-hidden="true">{{ $parts['icon'] }}</span>

                    @if ($score)
                        {{-- Цвет несёт смысл: высокая оценка золотая, средняя
                             янтарная, низкая приглушённо-красная. Раньше все
                             были одинаково жёлтыми, и «9.5» ничем не
                             отличалась от «4.0».
                             aria-label обязателен: без него экранный диктор
                             читает «звезда 8.0» без всякого контекста. --}}
                        <span class="gm-card__score {{ $score >= 8 ? 'is-high' : ($score >= 6 ? 'is-mid' : 'is-low') }}"
                              title="{{ __('frontend.gaming.score', ['score' => $score]) }}"
                              aria-label="{{ __('frontend.gaming.score', ['score' => $score]) }}">
                            {{-- ⚠️ Число и закрывающий тег на ОДНОЙ строке.
                                 Перенос ставит после него символ конца строки,
                                 и проверки свежести кеша (HomeCacheFreshnessTest)
                                 перестают находить `>7.0<`. --}}
                            <span class="gm-card__star" aria-hidden="true">★</span>{{ $score }}</span>
                    @endif
                </div>

                <div class="gm-card__body">
                    <h3 class="gm-card__title">{{ $parts['text'] }}</h3>
                    <p class="gm-card__text">{{ $excerptOf($item) }}</p>

                    {{-- Ссылки не было: правый нижний угол карточки пустовал,
                         и куда нажимать — приходилось угадывать. Порядок тот
                         же, что в «Журнале» и шаблоне по умолчанию. --}}
                    <span class="gm-card__more">{{ __('frontend.news.read_full') }} →</span>

                    {{-- Дата и время чтения разведены по углам и отделены
                         линией: одной строкой они сливались с анонсом. --}}
                    <span class="gm-card__meta">
                        <span class="gm-meta__date">{{ $item->created_at?->format('d.m.Y') }}</span>
                        <span class="gm-meta__time">{{ __('frontend.news.reading_time', ['min' => reading_time($item->content)]) }}</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Акцент — из активной темы. */
    .gm{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .gm__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,rgba(17,24,39,.08)); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .gm__badge{ display:flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        flex:none; font-size:1.2rem; background:var(--color-primary,#6366f1) }
    .gm__title{ margin:0; font-size:1.5rem; font-weight:700; color:var(--surface-ink,#111827); line-height:1.2 }
    .gm__sub{ margin:.1rem 0 0; font-size:.82rem; color:var(--surface-mute,#6b7280) }

    .gm-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(17.5rem,1fr)); gap:1rem }

    /* Карточка тёмная независимо от темы: так выглядят игровые витрины.
       Читаемость обеспечена собственным светлым текстом на своём фоне,
       а не наследованием цветов страницы. */
    .gm-card{ display:flex; flex-direction:column; background:#0f172a; border:1px solid #1e293b;
        overflow:hidden; transition:border-color .15s, transform .15s;

        /* Карточка тёмная ВСЕГДА, независимо от темы сайта, поэтому уровни
           текста переопределяются прямо на ней. Иначе внутри действуют общие
           переменные поверхностей, а они на светлой теме тёмные — и подпись
           с датой оказывались тёмными на тёмном: замер давал 3.47 и 3.64.
           Переопределение на самом элементе наследуется всем содержимым и
           не задевает остальную страницу. */
        --surface-ink:  #f1f5f9;
        --surface-mute: #cbd5e1;
        --surface-dim:  #94a3b8 }
    .gm-card:hover{ border-color:var(--color-accent,#8b5cf6); transform:translateY(-3px) }

    /* ⚠️ Пропорция, а не фиксированная высота. Ширина ячейки сетки
       плавает (auto-fill), и при 12.5rem рамка совпадала с обложкой 8:5
       только на одной конкретной ширине — на всех остальных картинку
       срезало. С aspect-ratio совпадение точное на любой раскладке. */
    .gm-card__media{ position:relative; width:100%; aspect-ratio:8 / 5; overflow:hidden;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .gm-card__media img{ width:100%; height:100%; object-fit:cover; display:block }

    /* Метка-эмодзи и плашка оценки — ПАРА в двух углах обложки, поэтому у
       них одна высота, одна обводка и одна тень. Раньше метка была заметно
       крупнее и темнее оценки (44 против 25) и читалась заплаткой, а сам
       значок терялся в её середине.

       ⚠️ border-radius здесь не задаётся намеренно: на сайте включён режим
       прямых краёв (body.fx-sharp * { border-radius:0 !important } в
       макете), и любое скругление в шаблоне — мёртвый код. Замер это и
       показал: у обеих плашек computed radius 0 при заданных 1.25rem. */
    .gm-card__tag{ position:absolute; top:.7rem; left:.7rem;
        display:inline-flex; align-items:center; justify-content:center;
        width:var(--gm-badge, 2rem); height:var(--gm-badge, 2rem); padding:0;
        font-size:1.05rem; line-height:1;
        background:rgba(15,23,42,.42); border:1px solid rgba(255,255,255,.35);
        backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
        box-shadow:0 6px 18px rgba(15,23,42,.45) }
    /* Плашка оценки. Цифры табличные — иначе «9.5» и «10.0» разной ширины
       и плашка «дышит» от карточки к карточке. Звезда отдельным элементом,
       чтобы приглушить её и не спорить с числом. */
    .gm-card__score{ position:absolute; bottom:.7rem; right:.7rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.25rem;
        height:var(--gm-badge, 2rem); padding:0 .6rem;
        font-size:.9rem; font-weight:800;
        font-variant-numeric:tabular-nums; letter-spacing:.01em; line-height:1;
        box-shadow:0 6px 18px rgba(15,23,42,.45);
        border:1px solid rgba(255,255,255,.35) }
    .gm-card__star{ font-size:.8em; opacity:.85 }
    /* Три ступени: цвет говорит об оценке раньше, чем прочитано число. */
    .gm-card__score.is-high{ color:#1c1917; background:linear-gradient(135deg,#fde047,#f59e0b) }
    .gm-card__score.is-mid{  color:#1c1917; background:linear-gradient(135deg,#fcd34d,#fb923c) }
    .gm-card__score.is-low{  color:#fff;    background:linear-gradient(135deg,#f87171,#dc2626) }

    .gm-card__body{ padding:.9rem 1rem 1.05rem; display:flex; flex-direction:column; gap:.35rem; flex:1 }
        /* Обрезка строками настоящим CSS: класса line-clamp в сборке нет. */
    .gm-card__title{ margin:0; font-size:1rem; line-height:1.3; font-weight:700; color:#f8fafc;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .gm-card__text{ margin:0; font-size:.82rem; line-height:1.5; color:var(--surface-dim,#94a3b8); flex:1;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }
    /* Ссылка у правого края, над строкой даты. Ширина по содержимому:
       растянутая на всю строку ловила бы нажатие на пустом месте слева. */
    .gm-card__more{ align-self:flex-end; font-size:.8rem; font-weight:700;
        color:var(--color-accent,#8b5cf6) }

    /* Нижняя строка карточки. Раньше это был обычный текст того же
       приглушённого цвета, что и анонс, — строка в нём тонула. Теперь
       отделена линией, приподнята фоном и разведена по краям. */
    .gm-card__meta{ display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        margin:.65rem -1rem -1.05rem; padding:.55rem 1rem; font-size:.75rem;
        border-top:1px solid #1e293b; background:#0b1220 }
    .gm-meta__date{ color:var(--surface-dim,#94a3b8); font-variant-numeric:tabular-nums }
    .gm-meta__date::before{ content:'🗓'; margin-right:.35rem; opacity:.75 }
    .gm-meta__time{ color:#cbd5e1; font-weight:600; white-space:nowrap }
    .gm-meta__time::before{ content:'⏱'; margin-right:.3rem; opacity:.75 }


    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы ниже.
       Цвета берутся из общих переменных поверхностей в макете. */
    body.fx-theme-dark .gm__head{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .gm__title{ color:var(--surface-ink) }

    /* ⚠️ Блока @media (prefers-color-scheme: dark) здесь больше нет.
       Это настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не тема сайта: у владельца
       система тёмная, а сайт светлый — шапка раздела уезжала в тёмный
       посреди светлой страницы. Тему сайта задаёт body.fx-theme-dark
       (правила выше), и только она. Тот же разбор — в CLAUDE.md. */

    /* ── Телефоны и планшеты ────────────────────────────────────────────
       Шаблон не имел адаптивных правил ВООБЩЕ — ни одного @media. */
    @media (max-width: 1024px), (max-height: 500px){
        .gm{ margin:1.5rem auto; padding:0 .75rem }

        /* Шапка раздела была inline-flex по содержимому: на узком экране
           длинный подзаголовок распирал её за край. */
        .gm__head{ display:flex; width:100%; box-sizing:border-box;
            padding:.6rem .8rem; margin-bottom:1rem }
        .gm__title{ font-size:1.15rem }
        .gm__sub{ font-size:12px }

        /* Ячейка 17.5rem (280) не влезала в 360 с полями — сетка давала
           горизонтальную прокрутку. Минимум снят, колонок столько,
           сколько поместится. */
        .gm-grid{ grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr)); gap:.75rem }

        /* Зоны нажатия и нижняя граница кегля. */
        .gm-card__more{ display:inline-flex; align-items:center; min-height:32px }
        /* Обе плашки растут одной переменной — иначе снова разъедутся. */
        .gm-card{ --gm-badge:2.3rem }
        .gm-card__tag{ font-size:1.15rem }
        .gm-meta__date, .gm-meta__time{ font-size:12px }
        .gm-card__text{ font-size:12px }
    }

    /* Телефон в альбомной: экран низкий, и карточка в полный рост
       вытесняет всё остальное. Анонс короче, обложка тоже. */
    @media (max-height: 500px) and (min-width: 640px){
        .gm-card__text{ -webkit-line-clamp:2 }
    }
</style>
@endpush
