{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🧰 ШАБЛОН «НАШИ УСЛУГИ»                                         ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Услугу выбирают по трём вещам: ЧТО делают, ЧТО входит в работу  ║
    ║  и СКОЛЬКО это стоит. Всё три на виду в карточке, без перехода.  ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = ourworks         ║
    ║    Цена — поле «Цена» в той же форме (пусто = «по запросу»).      ║
    ║                                                                  ║
    ║  ЗНАЧОК                                                          ║
    ║    Берётся из НАЧАЛА заголовка: «🚀 Установка» → значок 🚀, а в   ║
    ║    строке остаётся «Установка». Так владелец меняет значок прямо  ║
    ║    в заголовке, без отдельного поля и без правки шаблона.         ║
    ║    Нет эмодзи — рисуется общий значок услуги.                     ║
    ║                                                                  ║
    ║  ЧТО ВХОДИТ                                                      ║
    ║    Первые три пункта <li> из текста материала. Список в тексте    ║
    ║    услуги пишут почти всегда — отдельного поля заводить не нужно. ║
    ║                                                                  ║
    ║  ⚠️ ОБЛОЖКИ ЗДЕСЬ НЕТ НАМЕРЕННО                                  ║
    ║    Прежняя версия подставляла no-image.png каждой карточке: 192   ║
    ║    пикселя пустой рамки на услугу, у которой снимка не бывает.    ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Сначала $newsList — его отдаёт контроллер, сгруппировав материалы по
    // шаблонам. Остальные имена — из прежнего устройства главной.
    $worksList = $newsList
        ?? ($ourworksList ?? null)
        ?? ($serviceList ?? null)
        ?? ($templates['ourworks'] ?? collect());

    // Эмодзи в начале заголовка → значок карточки.
    // ⚠️ \X (графемный кластер) обязателен: флаги и значки с модификатором
    // цвета кожи состоят из нескольких кодовых точек, и посимвольный разбор
    // разрезал бы их пополам.
    $splitIcon = function (string $title): array {
        if (preg_match('~^(\X)\s+(\S.*)$~u', $title, $m)
            && preg_match('~[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}]~u', $m[1])) {
            return ['icon' => $m[1], 'label' => $m[2]];
        }

        return ['icon' => null, 'label' => $title];
    };

    // Сквозные номера услуг — свойство материала, а не позиция в списке.
    $numbers = template_numbers('ourworks');

    // Первые три пункта списка из текста услуги.
    $itemsOf = function (?string $html, int $limit = 3): array {
        if (! preg_match_all('~<li[^>]*>(.*?)</li>~su', (string) $html, $m)) {
            return [];
        }

        return array_slice(array_filter(array_map(
            fn ($s) => trim(preg_replace('~\s+~u', ' ', strip_tags($s))),
            $m[1]
        )), 0, $limit);
    };
@endphp

@if ($worksList->count())
<section class="sv">
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-briefcase"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.templates.ourworks') }}</h2>
            <p class="fx-section-sub">{{ __('frontend.ourworks.subtitle') }}</p>
        </div>
    </div>

    <div class="sv-grid">
        @foreach ($worksList as $work)
            @php
                $parts = $splitIcon((string) $work->title);
                $items = $itemsOf($work->content);
                $price = $work->price !== null && (float) $work->price > 0 ? (float) $work->price : null;
            @endphp

            <article class="sv-card">
                <div class="sv-card__head">
                    <span class="sv-card__icon" aria-hidden="true">
                        @if ($parts['icon'])
                            {{ $parts['icon'] }}
                        @else
                            <i class="fas fa-screwdriver-wrench"></i>
                        @endif
                    </span>

                    {{-- Порядковый номер моноширинным: он даёт списку услуг
                         структуру, по которой их называют в разговоре
                         («вторая услуга»), и ничего не стоит по месту.

                         СКВОЗНОЙ: заведена пятой — значит пятая. Раньше номер
                         был позицией в списке, поэтому добавленная услуга
                         получала 01 и сдвигала нумерацию всем остальным. --}}
                    <span class="sv-card__num" aria-hidden="true">{{ sprintf('%02d', $numbers[$work->id] ?? $loop->iteration) }}</span>
                </div>

                <h3 class="sv-card__title">
                    <a href="{{ route('news.show', $work->slug) }}">{{ $parts['label'] }}</a>
                </h3>

                <p class="sv-card__text">{{ content_excerpt($work->content, 150) }}</p>

                @if ($items)
                    <ul class="sv-card__list">
                        @foreach ($items as $item)
                            <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                @endif

                <div class="sv-card__foot">
                    <span class="sv-card__price">
                        @if ($price)
                            {{-- «от»: у услуги цена зависит от объёма, и точное
                                 число в карточке обещало бы больше, чем есть. --}}
                            <small>{{ __('frontend.ourworks.from') }}</small>
                            {{ number_format($price, 0, ',', ' ') }} ₽
                        @else
                            <b>{{ __('frontend.ourworks.on_request') }}</b>
                        @endif
                    </span>

                    <a href="{{ route('news.show', $work->slug) }}" class="sv-card__more">
                        {{ __('frontend.ourworks.details') }} →
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    {{-- ⚠️ method_exists: на главной список приходит обычной коллекцией
         (материалы уже сгруппированы по шаблонам), и hasPages() там нет —
         страница падала с 500, как только в базе появлялась услуга.
         Разметку постраничного вывода не пишем свою: общий компонент
         рендерят все 28 списков проекта. --}}
    @if (method_exists($worksList, 'hasPages') && $worksList->hasPages())
        <div class="sv-pager">{{ $worksList->links() }}</div>
    @endif
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет ни line-clamp, ни
       произвольных значений, ни прозрачности через дробь. Прежняя версия
       была собрана как раз из таких утилит, а цвета зашиты литералами
       (text-gray-900, bg-blue-600) — на тёмных темах заголовок пропадал. */
    .sv{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    /* Та же сетка, что у «Товаров» и «Отзывов»: разделы на одной странице
       должны читаться одним набором, а не тремя разными.
       min(100%, …) обязателен — иначе дорожка в 19rem не влезает в 360 с
       полями и вся страница получает горизонтальную прокрутку. */
    .sv-grid{ display:grid;
        grid-template-columns:repeat(auto-fill, minmax(min(100%,19rem), 1fr));
        gap:1rem }

    .sv-card{ display:flex; flex-direction:column; gap:.6rem;
        padding:1.15rem 1.2rem 1.05rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7);
        transition:border-color .18s ease, transform .18s ease, box-shadow .18s ease }
    .sv-card:hover{ border-color:color-mix(in srgb, var(--color-primary,#6366f1) 45%, var(--surface-bd,#eef2f7));
        transform:translateY(-3px);
        box-shadow:0 18px 40px -26px color-mix(in srgb, var(--color-primary,#6366f1) 60%, rgba(15,23,42,.5)) }
    .sv-card :focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    .sv-card__head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem }

    /* Значок услуги вместо обложки. Квадрат в цвете темы: он и опознаётся
       быстрее фотографии, и не тянет за собой 192 пикселя пустой рамки. */
    .sv-card__icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2.75rem; height:2.75rem; flex:none; font-size:1.35rem; line-height:1;
        color:var(--on-accent,#fff);
        background:linear-gradient(135deg, var(--color-primary,#6366f1), var(--color-accent,#8b5cf6)) }

    .sv-card__num{ font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:1.5rem; font-weight:800; line-height:1;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 22%, transparent) }

    .sv-card__title{ margin:0; font-size:1.05rem; line-height:1.35; font-weight:700 }
    .sv-card__title a{ color:var(--surface-ink,#111827); text-decoration:none;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .sv-card__title a:hover{ color:var(--color-primary,#6366f1) }

    .sv-card__text{ margin:0; font-size:.87rem; line-height:1.55;
        color:var(--surface-mute,#64748b);
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }

    /* Состав работ. Это и есть главный довод при выборе услуги, поэтому
       он стоит в карточке, а не только на странице материала. */
    .sv-card__list{ margin:.15rem 0 0; padding:0; list-style:none; flex:1;
        display:flex; flex-direction:column; gap:.32rem }
    .sv-card__list li{ display:flex; align-items:flex-start; gap:.45rem;
        font-size:.82rem; line-height:1.45; color:var(--surface-ink,#334155) }
    .sv-card__list i{ flex:none; margin-top:.25rem; font-size:.66rem;
        color:color-mix(in srgb, #16a34a 82%, var(--surface-ink,#111827)) }

    .sv-card__foot{ display:flex; align-items:baseline; justify-content:space-between;
        gap:.6rem; flex-wrap:wrap; padding-top:.7rem;
        border-top:1px solid var(--surface-bd,#f1f5f9) }

    .sv-card__price{ font-size:1.15rem; font-weight:800; letter-spacing:-.02em;
        color:var(--surface-ink,#111827); font-variant-numeric:tabular-nums;
        white-space:nowrap }
    /* «от» приглушено и мельче: это оговорка к числу, а не само число. */
    .sv-card__price small{ font-size:.7rem; font-weight:600; margin-right:.15rem;
        color:var(--surface-mute,#64748b) }
    .sv-card__price b{ font-size:.92rem; font-weight:700;
        color:var(--surface-mute,#64748b) }

    /* ⚠️ Чистый акцент здесь брать нельзя: фирменный индиго #6366f1 на белом
       даёт 4.47 при пороге 4.5, а 13-пиксельная строка под «крупный текст»
       не подпадает даже полужирной. Подмешиваем цвет текста — оттенок
       узнаётся, а читаемость выходит за порог. Доля 72% — та же, что
       уже применена в корзине: подмешивается ЦВЕТ ТЕКСТА, поэтому правило
       работает в обе стороны — на светлой теме темнит, на тёмной светлит. */
    .sv-card__more{ font-size:.84rem; font-weight:700; white-space:nowrap;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827)) }

    .sv-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы. Блока
       @media (prefers-color-scheme: dark) здесь нет намеренно: это
       настройка ОС, и при тёмной системе со светлым сайтом раздел уезжал
       бы в тёмный посреди светлой страницы (разбор — в CLAUDE.md). */
    body.fx-theme-dark .sv-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .sv-card__title a,
    body.fx-theme-dark .sv-card__price{ color:var(--surface-ink) }
    body.fx-theme-dark .sv-card__list li{ color:var(--surface-ink) }

    @media (max-width: 1024px), (max-height: 500px){
        .sv{ margin:1.5rem auto; padding:0 .75rem }
        .sv-grid{ gap:.75rem }
        .sv-card{ padding:1rem }
        .sv-card__icon{ width:2.5rem; height:2.5rem; font-size:1.2rem }
        .sv-card__list li{ font-size:12px }
        /* Ссылка — зона нажатия, а не просто текст. */
        .sv-card__more{ display:inline-flex; align-items:center; min-height:32px }
    }

    /* Движение отключается по настройке «уменьшить анимацию». */
    @media (prefers-reduced-motion: reduce){
        .sv-card{ transition:none }
        .sv-card:hover{ transform:none }
    }
</style>
@endpush
