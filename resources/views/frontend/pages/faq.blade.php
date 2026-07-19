@extends('layouts.frontend')

@section('title', 'FAQ — Монтаж металлоконструкций, эвакуация авто, спил деревьев, подъём грузов')

@push('head')
    <meta name="description"
        content="FAQ: монтаж металлоконструкций, эвакуация авто, подъём и перевозка грузов, спил деревьев, условия, техника (вылет 24 м, г/п 7 т, кузов 12 т, 7 м — 10 палет), прайс и безопасность. 24/7." />
@endpush

@section('content')
    @push('styles')
        <style>
            :root {
                color-scheme: light dark;
                --ui-font: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Noto Sans", Arial, "Apple Color Emoji", "Segoe UI Emoji";
                --radius: 12px;
                --gap: .75rem;
                --bg: #fff;
                --fg: #0f172a;
                --muted: #64748b;
                --primary: #2563eb;
                --border: color-mix(in oklab, var(--fg) 12%, #e5e7eb);
                --ring: color-mix(in oklab, var(--primary) 55%, #93c5fd);
                --card: var(--bg);
                --maxw: 72rem;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0b1220;
                    --fg: #e5e7eb;
                    --muted: #9ca3af;
                    --border: color-mix(in oklab, var(--fg) 15%, #111827);
                    --card: #0f172a
                }
            }

            @media (prefers-reduced-motion: reduce) {
                * {
                    animation: none !important;
                    transition: none !important;
                    scroll-behavior: auto !important
                }
            }

            @media (forced-colors: active) {

                .btn,
                .chip,
                .card,
                .input,
                .pill,
                .counter {
                    forced-color-adjust: auto
                }
            }

            #faq-root {
                font-family: var(--ui-font);
                color: var(--fg)
            }

            .page-wrap {
                max-width: var(--maxw);
                margin-inline: auto;
                padding-inline: 1rem
            }

            /* HERO */
            .hero {
                padding: 1rem;
                border-block: 1px solid var(--border);
                margin-block: 0 1rem
            }

            @media (min-width:768px) {
                .hero {
                    padding: 1.25rem 1rem;
                    margin-bottom: 1.25rem
                }
            }

            .hero-ico {
                width: 48px;
                height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: color-mix(in oklab, var(--primary) 10%, var(--bg))
            }

            h1.hero-title {
                font-size: clamp(1.4rem, 1rem + 2.5vw, 2rem);
                line-height: 1.2;
                margin: 0
            }

            .muted {
                color: var(--muted)
            }

            .anchors {
                display: flex;
                gap: .5rem;
                flex-wrap: wrap;
                margin: .75rem 0 0
            }

            .chip {
                border: 1px solid var(--border);
                border-radius: 9999px;
                padding: .35rem .7rem;
                font-size: .88rem
            }

            /* Controls */
            .controls {
                display: grid;
                gap: .5rem
            }

            .searchbox {
                position: relative
            }

            .input {
                width: 100%;
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: .62rem 2.6rem .62rem 2.25rem;
                background: transparent
            }

            .input:focus {
                outline: 2px solid var(--ring);
                outline-offset: 2px
            }

            .search-ico {
                position: absolute;
                left: .7rem;
                inset-block: 0;
                display: flex;
                align-items: center;
                opacity: .6
            }

            .clear-btn {
                position: absolute;
                right: .3rem;
                inset-block: 0;
                display: flex;
                align-items: center;
                padding: 0 .5rem;
                font-size: .8rem;
                opacity: .7
            }

            .clear-btn[hidden] {
                display: none
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
                font-size: .8rem
            }

            .counter {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                padding: .35rem .6rem;
                border-radius: 9999px;
                background: color-mix(in oklab, var(--bg)94%, var(--fg))
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                padding: .4rem .7rem;
                border: 1px solid var(--border);
                border-radius: 9999px;
                background: transparent
            }

            .btn:focus {
                outline: 2px solid var(--ring);
                outline-offset: 2px
            }

            /* Cards */
            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 1rem
            }

            @media (min-width:768px) {
                .card {
                    padding: 1.25rem
                }
            }

            /* FAQ items */
            #faqList {
                --qgap: .5rem
            }

            @media (min-width:768px) {
                #faqList {
                    --qgap: .6rem
                }
            }

            details.faq {
                border: 1px solid var(--border);
                border-radius: 12px;
                overflow: hidden
            }

            details.faq+details.faq {
                margin-top: var(--qgap)
            }

            details.faq>summary {
                display: flex;
                align-items: center;
                gap: .6rem;
                padding: .75rem 1rem;
                cursor: pointer;
                font-weight: 600;
                list-style: none
            }

            details.faq[open]>summary .chev {
                transform: rotate(180deg)
            }

            details.faq .body {
                padding: 0 1rem 1rem;
                font-size: .95rem;
                opacity: .92
            }

            .copy-link {
                margin-left: .5rem;
                font-size: .78rem;
                text-decoration: underline;
                text-underline-offset: 2px;
                opacity: .65
            }

            .chev {
                margin-left: auto;
                opacity: .6;
                transition: transform .2s
            }

            /* Guides grid */
            .grid {
                display: grid;
                gap: 1rem
            }

            @media (min-width:768px) {
                .grid-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr))
                }
            }

            /* Utilities */
            .row {
                display: flex;
                gap: .75rem;
                flex-wrap: wrap;
                align-items: flex-start
            }

            .spacer {
                flex: 1
            }

            .underline {
                text-decoration: underline;
                text-underline-offset: 2px
            }

            /* === Force-light только для дарк-темы ОС/браузера (локально на FAQ) === */
            @media (prefers-color-scheme: dark) {

                /* говорим браузеру рендерить контролы как в светлой схеме */
                #faq-root {
                    color-scheme: light;
                }

                /* возвращаем светлые переменные именно для FAQ */
                #faq-root {
                    --bg: #ffffff;
                    --fg: #0f172a;
                    --muted: #64748b;
                    --primary: #2563eb;
                    --border: #e5e7eb;
                    --ring: #93c5fd;
                    --card: #ffffff;
                }

                /* базовый текст и вторичный */
                #faq-root,
                #faq-root * {
                    color: var(--fg)
                }

                #faq-root .muted {
                    color: var(--muted) !important;
                }

                /* карточки/деталы/инпуты/кнопки – белый фон и светлые бордеры */
                #faq-root .card,
                #faq-root details.faq,
                #faq-root .input,
                #faq-root .btn,
                #faq-root .chip,
                #faq-root .counter {
                    background: #ffffff !important;
                    border-color: #e5e7eb !important;
                    color: #0f172a !important;
                }

                /* ссылки – как в светлой теме */
                #faq-root a {
                    color: #2563eb !important;
                }

                #faq-root a:hover {
                    color: #1d4ed8 !important;
                }

                /* плейсхолдеры поиска */
                #faq-root ::placeholder {
                    color: #94a3b8;
                    opacity: 1;
                }

                /* подчёркивание найденного (из JS-подсветки) – мягкое */
                #faq-root .hl {
                    background: #fff59d;
                    color: inherit;
                }
            }
        </style>
    @endpush

    <article id="faq-root" class="page-wrap" aria-labelledby="faq-title">
        {{-- ===== HERO ===== --}}
        <div class="hero">
            <div class="row">
                <div class="hero-ico" aria-hidden="true">@themeIcon('wallet', 'w-5 h-5 opacity-80')</div>
                <div class="spacer">
                    <h1 id="faq-title" class="hero-title no-italic">FAQ — Часто задаваемые вопросы</h1>
                    <p class="muted no-italic" style="margin:.5rem 0 0">
                        Монтаж металлоконструкций, эвакуация авто, подъём и перевозка грузов, спил деревьев. Здесь — про
                        технику, сроки, прайс, безопасность и 24/7.
                    </p>
                    <div class="anchors" aria-label="Навигация по разделам">
                        <a class="chip" href="#faqSection">Вопросы</a>
                        <a class="chip" href="#guides">Памятки</a>
                    </div>
                </div>
            </div>

            {{-- Controls --}}
            <div class="controls" style="margin-top:1rem">
                <div class="searchbox">
                    <input id="faqSearch" class="input" type="search"
                        placeholder="Найдите ответ (нажмите / для быстрого поиска)…" aria-label="Поиск по вопросам" />
                    <button id="clearSearch" class="clear-btn" aria-label="Очистить поиск" hidden>Очистить</button>
                </div>
                <div class="actions" aria-live="polite">
                    <span class="counter">Всего: <b id="resultCountNum">—</b></span>
                    <button data-expansion="expand-all" class="btn">Развернуть всё</button>
                    <button data-expansion="collapse-all" class="btn">Свернуть всё</button>
                </div>
            </div>
        </div>

        {{-- ===== FAQ LIST ===== --}}
        <section id="faqSection" class="card" aria-label="Список вопросов">
            <div id="faqList" data-keep-open-from-hash>

                {{-- 1. Стоимость/минимальный заказ --}}
                <details id="q-price" class="faq" aria-labelledby="q-price-summary">
                    <summary id="q-price-summary">
                        @themeIcon('wallet', 'w-4 h-4 opacity-70')
                        <span>Сколько стоит вызов и каков минимальный заказ?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" aria-label="Скопировать ссылку" title="Скопировать ссылку"
                            data-anchor="#q-price">ссылка</button>
                    </summary>
                    <div class="body">
                        Минимальный заказ — <b>от 2 часов</b>. Базовая ставка — <b>от 3&nbsp;000&nbsp;руб./час</b> (в
                        зависимости от задачи и сложности).
                        Выезд за город — <b>200&nbsp;руб./км</b> от границы города.<br>
                        Негабарит и сложные такелажные операции рассчитываются индивидуально. Работаем <b>24/7</b> для
                        срочных заявок.
                        Связаться: <a href="tel:+79300373536" class="underline"
                            style="color:var(--primary)">8-930-037-35-36</a> или VK <a href="https://vk.com/club228649931"
                            target="_blank" rel="noopener" class="underline"
                            style="color:var(--primary)">vk.com/club228649931</a>.
                    </div>
                </details>

                {{-- 2. Характеристики техники --}}
                <details id="q-methods" class="faq">
                    <summary>
                        @themeIcon('truck', 'w-4 h-4 opacity-70')
                        <span>Какая у вас техника и возможности по подъёму/перевозке?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-methods">ссылка</button>
                    </summary>
                    <div class="body">
                        • Вылет стрелы — <b>до 24&nbsp;м</b> • Грузоподъёмность стрелы — <b>до 7&nbsp;т</b>.<br>
                        • Кузов — <b>г/п 12&nbsp;т</b>, длина <b>7&nbsp;м</b> (до <b>10 палет</b>).<br>
                        • Есть <b>монтажная корзина</b> для работ на высоте (строго со страховкой и СИЗ).<br>
                        • Перевозим стройматериалы/оборудование, возможен негабарит — по согласованию с расчётом маршрута.
                    </div>
                </details>

                {{-- 3. Нет подъезда / стеснённые условия --}}
                <details id="q-noaccess" class="faq">
                    <summary>
                        @themeIcon('map', 'w-4 h-4 opacity-70')
                        <span>Что, если подъезда нет (двор, плотная застройка, узкие арки)?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-noaccess">ссылка</button>
                    </summary>
                    <div class="body">
                        Работаем в стеснённых условиях: используем компактные схемы подъёма, верёвочный доступ,
                        сборно-разборные решения.
                        Предлагаем вариативные ППР, чтобы бережно пройти рядом с кровлями, заборами, деревьями и
                        коммуникациями.
                    </div>
                </details>

                {{-- 4. Перевозка / негабарит --}}
                <details id="q-disposal" class="faq">
                    <summary>
                        @themeIcon('boxes', 'w-4 h-4 opacity-70')
                        <span>Перевозите стройматериалы и негабаритный груз?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-disposal">ссылка</button>
                    </summary>
                    <div class="body">
                        Да. Перевозим стандартные паллетные позиции и длинномеры. Негабарит — возможен после согласования
                        габаритов, веса и маршрута.
                        Стоимость рассчитываем индивидуально, исходя из рисков и требований к сопровождению.
                    </div>
                </details>

                {{-- 5. Монтаж металлоконструкций --}}
                <details id="q-stump" class="faq">
                    <summary>
                        @themeIcon('eye', 'w-4 h-4 opacity-70')
                        <span>Какие работы по монтажу металлоконструкций выполняете?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-stump">ссылка</button>
                    </summary>
                    <div class="body">
                        Сборка ферм, колонн, балок; болтовые/сварные соединения, выверка и контроль геометрии. Подача на
                        высоту, временная фиксация,
                        затем окончательная сборка по проекту. Чистота и безопасность на объекте — в стандарте.
                    </div>
                </details>

                {{-- 6. Территория работ --}}
                <details id="q-where" class="faq">
                    <summary>
                        @themeIcon('globe', 'w-4 h-4 opacity-70')
                        <span>Где вы работаете?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-where">ссылка</button>
                    </summary>
                    <div class="body">
                        Курск и Курская область. Выезд в соседние регионы — по договорённости (учитываем плечо и логистику).
                    </div>
                </details>

                {{-- 7. Безопасность / корзина --}}
                <details id="q-safety" class="faq">
                    <summary>
                        @themeIcon('shield', 'w-4 h-4 opacity-70')
                        <span>Как обеспечивается безопасность? Есть ли правила работы в корзине?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-safety">ссылка</button>
                    </summary>
                    <div class="body">
                        Перед началом — ППР: схема строповки/подъёма, ограждение зоны, сигнальная связь
                        «машинист-стропальщик».
                        В корзине — <b>обязательная страховка</b>, каски/привязи, контроль ветра и погодных ограничений.
                        Работаем аккуратно рядом с фасадами и инженерией.
                    </div>
                </details>

                {{-- 8. Сроки/оперативность, 24/7 --}}
                <details id="q-timing" class="faq">
                    <summary>
                        @themeIcon('clock', 'w-4 h-4 opacity-70')
                        <span>Как быстро можете приехать и работаете ли 24/7?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-timing">ссылка</button>
                    </summary>
                    <div class="body">
                        Стартуем в ближайшие свободные окна. Аварийные выезды — по возможности <b>день-в-день</b>.
                        Связь и заявки принимаем <b>24/7</b> по телефону <a href="tel:+79300373536" class="underline"
                            style="color:var(--primary)">8-930-037-35-36</a> (WhatsApp/Telegram привязаны) и в VK:
                        <a href="https://vk.com/club228649931" target="_blank" class="underline"
                            style="color:var(--primary)">vk.com/club228649931</a>.
                    </div>
                </details>

                {{-- 9. Что подготовить заказчику --}}
                <details id="q-prepare" class="faq">
                    <summary>
                        @themeIcon('list', 'w-4 h-4 opacity-70')
                        <span>Что подготовить перед работами/перевозкой?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-prepare">ссылка</button>
                    </summary>
                    <div class="body">
                        Обеспечить подъезд, убрать хрупкие предметы, предупредить соседей. По грузам — заранее сообщить
                        вес/габариты/крепёжные точки.
                        Для деревьев — доступ к зоне работ. Полный чек-лист дадим после осмотра/брифа.
                    </div>
                </details>

                {{-- 10. Уборка/вывоз после работ --}}
                <details id="q-cleanup" class="faq">
                    <summary>
                        @themeIcon('map', 'w-4 h-4 opacity-70')
                        <span>Наводите ли порядок после работ? Вывозите остатки?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-cleanup">ссылка</button>
                    </summary>
                    <div class="body">
                        Да. По деревьям — вывоз/укладка порубочных остатков по договорённости. По монтажу — уборка рабочей
                        зоны, вывоз пакета материалов при необходимости.
                    </div>
                </details>

                {{-- 11. Организации/документы --}}
                <details id="q-org" class="faq">
                    <summary>
                        @themeIcon('building', 'w-4 h-4 opacity-70')
                        <span>Работаете с организациями и по документам?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-org">ссылка</button>
                    </summary>
                    <div class="body">
                        Да. Смета, договор, акты и закрывающие документы. Опыт работы с ТСЖ, школами, предприятиями и
                        муниципальными заказчиками.
                    </div>
                </details>

                {{-- 12. Разрешения/согласования --}}
                <details id="q-permit" class="faq">
                    <summary>
                        @themeIcon('file-text', 'w-4 h-4 opacity-70')
                        <span>Нужны ли разрешения: деревья/негабарит?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-permit">ссылка</button>
                    </summary>
                    <div class="body">
                        Для деревьев во дворах/городе действуют муниципальные нормы. Для негабарита — согласование маршрута
                        и возможное сопровождение.
                        Подскажем конкретно по вашей задаче.
                    </div>
                </details>

                {{-- 13. Оплата --}}
                <details id="q-payment" class="faq">
                    <summary>
                        @themeIcon('credit-card', 'w-4 h-4 opacity-70')
                        <span>Как оплатить работы?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-payment">ссылка</button>
                    </summary>
                    <div class="body">
                        Наличные и безналичный расчёт. Для юрлиц — по договору и счёту.
                    </div>
                </details>

                {{-- 14. Погода/сезонность --}}
                <details id="q-weather" class="faq">
                    <summary>
                        @themeIcon('sun', 'w-4 h-4 opacity-70')
                        <span>Работаете зимой и в непогоду/ночью?</span>
                        <span class="chev">@themeIcon('chevron-down', 'w-4 h-4')</span>
                        <button class="copy-link" data-anchor="#q-weather">ссылка</button>
                    </summary>
                    <div class="body">
                        Да, круглый год и в ночное время по заявке. При штормовом ветре/осадках переносим — безопасность
                        важнее сроков.
                    </div>
                </details>

            </div>
        </section>

        {{-- ===== GUIDES ===== --}}
        <section id="guides" class="grid grid-2" style="margin-top:1rem">
            <details class="card">
                <summary class="no-italic font-semibold cursor-pointer select-none flex items-center gap-2">
                    @themeIcon('server', 'w-4 h-4 opacity-70')
                    <span>Памятка: безопасный подъём/работа в корзине</span>
                    <span class="spacer"></span>
                    @themeIcon('chevron-down', 'w-4 h-4 opacity-60')
                </summary>
                <div class="muted" style="margin-top:.75rem">
                    <ol class="list-decimal list-inside" style="display:grid;gap:.5rem">
                        <li>Осмотр площадки, маркировка опасных зон, согласование траекторий.</li>
                        <li>ППР: схема строповки, точки крепления, сигнальная связь, допуски.</li>
                        <li>Сбор/проверка СИЗ: каски, привязи, фалы. В корзине — страховка обязательна.</li>
                        <li>Контроль ветра/погоды, проверка состояния техники перед стартом.</li>
                        <li>Плавные манёвры, запрет нахождения людей под грузом, ограждение периметра.</li>
                        <li>Финальная установка/перевозка, уборка и сдача объекта.</li>
                    </ol>
                </div>
            </details>

            <div class="card">
                <h3 class="no-italic font-semibold row">@themeIcon('book-open', 'w-4 h-4 opacity-70') <span>Полезные ссылки</span></h3>
                <ul class="muted" style="margin:.5rem 0 0;display:grid;gap:.3rem;font-size:.95rem">
                    <li><a href="{{ url('/about') }}" class="underline" style="color:var(--primary)">О компании</a></li>
                    <li><a href="{{ url('/contacts') }}" class="underline" style="color:var(--primary)">Контакты</a></li>
                    <li><a href="https://vk.com/club228649931" target="_blank" class="underline"
                            style="color:var(--primary)">Мы во ВКонтакте</a></li>
                </ul>
            </div>
        </section>

        <div class="page-wrap" style="padding:1rem 0 0">
            <div class="card" role="complementary">
                <div class="row">
                    <p class="muted" style="margin:0">Не нашли ответ? Опишите задачу — подскажем решение и смету.</p>
                    <span class="spacer"></span>
                    <a href="{{ url('/contacts') }}" class="btn" style="border-radius:10px">Открыть «Контакты»</a>
                </div>
            </div>
        </div>
    </article>

@endSection

@push('scripts')
    <script>
        (function() {
            const $ = (s, r = document) => r.querySelector(s);
            const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
            const input = $('#faqSearch');
            const clearBtn = $('#clearSearch');
            const items = $$('#faqList details');
            const counter = $('#resultCountNum');
            const expandBtn = document.querySelector('[data-expansion="expand-all"]');
            const collapseBtn = document.querySelector('[data-expansion="collapse-all"]');

            window.addEventListener('keydown', (e) => {
                if (e.key === '/' && document.activeElement !== input) {
                    e.preventDefault();
                    input?.focus();
                }
                if (e.key === 'Escape') {
                    input?.blur();
                }
            });

            function normalize(s) {
                return (s || '').toLowerCase().trim();
            }

            function clearMarks(root) {
                $$('.hl', root).forEach(n => n.replaceWith(...n.childNodes));
            }

            function highlight(root, q) {
                clearMarks(root);
                if (!q) return;
                const rx = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
                const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
                const nodes = [];
                while (walker.nextNode()) nodes.push(walker.currentNode);
                nodes.forEach(node => {
                    const text = node.nodeValue;
                    if (!text) return;
                    const html = text.replace(rx, '<mark class="hl">$1</mark>');
                    if (html !== text) {
                        const span = document.createElement('span');
                        span.innerHTML = html;
                        node.replaceWith(...span.childNodes);
                    }
                });
            }

            function applyFilter(q) {
                const nq = normalize(q);
                let visible = 0;
                items.forEach(d => {
                    const content = normalize(d.innerText);
                    const match = !nq || content.includes(nq);
                    d.style.display = match ? '' : 'none';
                    if (match) {
                        visible++;
                        d.open = !!nq;
                        highlight(d, nq);
                    } else {
                        clearMarks(d);
                    }
                });
                counter.textContent = String(visible);
                clearBtn.hidden = !nq;
            }

            const key = 'faq-open';

            function saveState() {
                const openIds = items.filter(d => d.open).map(d => d.id);
                sessionStorage.setItem(key, JSON.stringify(openIds));
            }

            function loadState() {
                try {
                    const openIds = JSON.parse(sessionStorage.getItem(key) || '[]');
                    items.forEach(d => d.open = openIds.includes(d.id));
                } catch (e) {}
            }
            items.forEach(d => d.addEventListener('toggle', saveState));

            function openFromHash() {
                const id = location.hash.slice(1);
                if (!id) return;
                const el = document.getElementById(id);
                if (el && el.tagName.toLowerCase() === 'details') {
                    el.open = true;
                    el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
            window.addEventListener('hashchange', openFromHash);

            $$('.copy-link').forEach(btn => btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const anchor = btn.dataset.anchor || '#';
                const url = new URL(location.href);
                url.hash = anchor;
                try {
                    await navigator.clipboard.writeText(url.toString());
                    btn.textContent = 'скопировано';
                    setTimeout(() => btn.textContent = 'ссылка', 1200);
                } catch (_) {}
            }));

            expandBtn?.addEventListener('click', () => items.forEach(d => (d.style.display = '', d.open = true)));
            collapseBtn?.addEventListener('click', () => items.forEach(d => d.open = false));

            loadState();
            applyFilter(input?.value || '');
            openFromHash();
            input?.addEventListener('input', () => applyFilter(input.value));
            clearBtn?.addEventListener('click', () => {
                input.value = '';
                input.focus();
                applyFilter('');
            });
        })();
    </script>
@endpush

@push('head')
    <script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => [
    ['@type'=>'Question','name'=>'Сколько стоит вызов и каков минимальный заказ?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Минимальный заказ — от 2 часов. Базовая ставка — от 3 000 руб./час (по задаче/сложности). Выезд за город — 200 руб./км от границы. Негабарит — по индивидуальному расчёту. Работаем 24/7.']],
    ['@type'=>'Question','name'=>'Какая у вас техника и возможности?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Вылет стрелы до 24 м, грузоподъёмность до 7 т. Кузов г/п 12 т, длина 7 м — до 10 палет. Есть монтажная корзина (работа только со страховкой). Перевозим стройматериалы и оборудование, возможен негабарит.']],
    ['@type'=>'Question','name'=>'Можно ли работать в стеснённых дворах и без подъезда?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Да. Используем компактные схемы подъёма, верёвочный доступ, поэтапную подачу и безопасные траектории.']],
    ['@type'=>'Question','name'=>'Перевозите ли стройматериалы и негабарит?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Да. Стандарт и длинномеры — по базовым тарифам, негабарит — после согласования габаритов/веса/маршрута.']],
    ['@type'=>'Question','name'=>'Какие работы по монтажу выполняете?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Сборка ферм, колонн, балок; болтовые/сварные соединения, выверка и контроль геометрии, подача на высоту, финальная сборка.']],
    ['@type'=>'Question','name'=>'Где вы работаете?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Курск и Курская область. Выезд в соседние регионы — по договорённости.']],
    ['@type'=>'Question','name'=>'Как обеспечивается безопасность и как работать в корзине?','acceptedAnswer'=>['@type'=>'Answer','text'=>'ППР, ограждение зоны, сигнальная связь. В корзине обязательны каски и привязи, контроль ветра и ограничений.']],
    ['@type'=>'Question','name'=>'Как быстро можете приехать и работаете ли 24/7?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Старт в ближайшие окна, аварийные выезды — день-в-день по возможности. Связь и заявки — 24/7.']],
    ['@type'=>'Question','name'=>'Что подготовить перед работами?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Обеспечить подъезд и доступ, убрать хрупкие предметы, сообщить вес/габариты и точки крепления.']],
    ['@type'=>'Question','name'=>'Наводите ли порядок после работ?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Да. По деревьям — вывоз/укладка порубочных остатков, по монтажу — уборка зоны и вывоз упаковки при необходимости.']],
    ['@type'=>'Question','name'=>'Работаете ли с организациями и по документам?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Да. Смета, договор, акты и закрывающие документы.']],
    ['@type'=>'Question','name'=>'Нужны ли разрешения на деревья/негабарит?','acceptedAnswer'=>['@type'=>'Answer','text'=>'В городе — муниципальные нормы на деревья; для негабарита — согласование маршрута и возможное сопровождение.']],
    ['@type'=>'Question','name'=>'Как оплатить работы?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Наличные и безналичный расчёт; для юрлиц — по счёту и договору.']],
    ['@type'=>'Question','name'=>'Работаете зимой и в непогоду/ночью?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Да, круглый год и по ночам по заявке. При штормовом ветре/осадках переносим ради безопасности.']],
  ]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
