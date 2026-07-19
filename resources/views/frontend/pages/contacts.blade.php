@extends('layouts.frontend')

@section('title', 'Контакты — Спил деревьев и манипулятор 2 в 1, Курск')

@push('head')
    <meta name="description"
        content="Контакты: спил деревьев и вывоз манипулятором в Курске и области. Телефон 8 (930) 037-35-36, городской 305-008, e-mail Suglobov2015@mail.ru. Работаем 24/7." />
@endpush

@section('content')
    @php
        // Пытаемся найти публичный роут модуля сообщений (если есть)
        $formAction = null;
        if (\Illuminate\Support\Facades\Route::has('messages.public.store')) {
            $formAction = route('messages.public.store');
        } elseif (\Illuminate\Support\Facades\Route::has('messages.store')) {
            $formAction = route('messages.store');
        }
    @endphp

    @push('styles')
        <style>
            :root {
                color-scheme: light dark;
                --ui-font: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Noto Sans", Arial, "Apple Color Emoji", "Segoe UI Emoji";
                --radius: 12px;
                --gap: .75rem;
                --bg: #ffffff;
                --fg: #0f172a;
                --muted: #64748b;
                --primary: #2563eb;
                --ring: color-mix(in oklab, var(--primary) 55%, #93c5fd);
                --border: color-mix(in oklab, var(--fg) 12%, #e5e7eb);
                --card: var(--bg);
                --maxw: 72rem;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0b1220;
                    --fg: #e5e7eb;
                    --muted: #9ca3af;
                    --border: color-mix(in oklab, var(--fg) 15%, #111827);
                    --card: #0f172a;
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
                .input,
                .pill,
                .card {
                    forced-color-adjust: auto
                }
            }

            #contacts-root {
                font-family: var(--ui-font);
                color: var(--fg);
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
                content-visibility: auto;
                contain-intrinsic-size: 900px 600px
            }

            .page-wrap {
                max-width: var(--maxw);
                margin-inline: auto;
                padding-inline: 1rem
            }

            .page-hero {
                position: relative;
                padding: 1rem;
                border-block: 1px solid var(--border);
                margin-block: 0 1rem
            }

            @media (min-width:768px) {
                .page-hero {
                    padding: 1.25rem 1rem;
                    margin-bottom: 1.25rem
                }
            }

            .crumbs {
                display: flex;
                gap: .5rem;
                flex-wrap: wrap;
                align-items: center;
                font-size: .85rem;
                color: var(--muted)
            }

            .crumbs a {
                text-decoration: underline;
                color: inherit
            }

            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 1rem
            }

            @media (min-width:768px) {
                .card {
                    padding: 1.25rem
                }
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                padding: .56rem .9rem;
                border-radius: 10px;
                font-weight: 600
            }

            .btn:focus {
                outline: 2px solid var(--ring);
                outline-offset: 2px
            }

            .btn-primary {
                background: var(--primary);
                color: #fff
            }

            .btn-primary:hover {
                filter: brightness(.96)
            }

            .btn-outline {
                background: transparent;
                border: 1px solid var(--border);
                color: inherit
            }

            .kv-label {
                font-size: .75rem;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--muted)
            }

            .hyphens {
                hyphens: auto;
                -webkit-hyphens: auto;
                overflow-wrap: anywhere;
                word-break: break-word
            }

            .no-italic {
                font-style: normal !important
            }

            .muted {
                color: var(--muted)
            }

            .hero-ico {
                width: 40px;
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: color-mix(in oklab, var(--primary) 10%, var(--bg))
            }

            #contacts-root h1 {
                font-size: clamp(1.35rem, 1rem + 2.2vw, 1.9rem);
                line-height: 1.25
            }

            .header-actions {
                display: flex;
                gap: .5rem;
                width: 100%
            }

            .header-actions>a {
                flex: 1;
                justify-content: center
            }

            @media (min-width:768px) {
                .header-actions {
                    width: auto
                }

                .header-actions>a {
                    flex: 0 0 auto
                }
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

            .social-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .5rem
            }

            @media (min-width:480px) {
                .social-list {
                    grid-template-columns: repeat(3, minmax(0, 1fr))
                }
            }

            @media (min-width:768px) {
                .social-list {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: .6rem
                }
            }

            .pill {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: .5rem;
                border: 1px solid var(--border);
                border-radius: 10px;
                min-height: 42px;
                padding: .5rem .75rem;
                background: transparent
            }

            .pill:hover {
                background: color-mix(in oklab, var(--bg) 92%, var(--fg))
            }

            .pill>svg {
                width: .95rem;
                height: .95rem;
                flex: 0 0 .95rem
            }

            .input {
                width: 100%;
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: .62rem .8rem;
                background: transparent
            }

            .input:focus {
                outline: 2px solid var(--ring);
                outline-offset: 2px
            }

            .textarea {
                min-height: 120px
            }

            .help {
                font-size: .8rem;
                color: var(--muted)
            }

            .error {
                font-size: .8rem;
                color: #b91c1c
            }

            .field {
                display: block;
                font-size: .92rem
            }

            .field>span {
                display: block;
                margin-bottom: .3rem;
                color: var(--muted)
            }

            .map-wrap {
                position: relative;
                overflow: hidden;
                border-radius: 10px
            }

            .map-wrap::before {
                content: "";
                display: block;
                padding-top: 58%
            }

            @media (min-width:768px) {
                .map-wrap::before {
                    padding-top: 44%
                }
            }

            .map-frame {
                position: absolute;
                inset: 0;
                border: 0;
                width: 100%;
                height: 100%
            }

            .grid2 {
                display: grid;
                grid-template-columns: 1fr;
                gap: var(--gap)
            }

            @media (min-width:768px) {
                .grid2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr))
                }
            }

            @media print {

                .anchors,
                .header-actions,
                .map-wrap iframe {
                    display: none !important
                }

                .page-hero {
                    border: none;
                    padding: 0;
                    margin: 0 0 .5rem
                }
            }

            /* === Force-light для страницы "Контакты" при дарк-режиме ОС/браузера === */
            @media (prefers-color-scheme: dark) {

                /* рендер форм/скроллбаров как в light */
                #contacts-root {
                    color-scheme: light;
                }

                /* возвращаем светлую палитру именно внутри #contacts-root */
                #contacts-root {
                    --bg: #ffffff;
                    --fg: #0f172a;
                    --muted: #64748b;
                    --primary: #2563eb;
                    --ring: #93c5fd;
                    --border: #e5e7eb;
                    --card: #ffffff;
                }

                /* базовые тексты */
                #contacts-root,
                #contacts-root * {
                    color: var(--fg);
                }

                #contacts-root .muted {
                    color: var(--muted) !important;
                }

                /* карточки/кнопки/инпуты/пилюли/чипы — белый фон и светлые бордеры */
                #contacts-root .card,
                #contacts-root .input,
                #contacts-root .textarea,
                #contacts-root .btn,
                #contacts-root .pill,
                #contacts-root .chip {
                    background: #ffffff !important;
                    border-color: #e5e7eb !important;
                    color: #0f172a !important;
                }

                /* outline-кнопка остаётся прозрачной */
                #contacts-root .btn.btn-outline {
                    background: transparent !important;
                }

                /* hover для плиток-ссылок — лёгкая подсветка как в лайте */
                #contacts-root .pill:hover {
                    background: rgba(15, 23, 42, .04) !important;
                }

                /* ссылки — привычный синий */
                #contacts-root a {
                    color: #2563eb !important;
                }

                #contacts-root a:hover {
                    color: #1d4ed8 !important;
                }

                /* плейсхолдеры */
                #contacts-root ::placeholder {
                    color: #94a3b8;
                    opacity: 1;
                }

                /* карта остаётся как есть, но рамки блока — светлые */
                #contacts-root .map-wrap {
                    background: #ffffff;
                    border: 1px solid #e5e7eb;
                }
            }
        </style>
    @endpush

    <article id="contacts-root" class="page-wrap hyphens" aria-labelledby="contacts-title">
        {{-- ===== HERO ===== --}}
        <div class="page-hero">
            <nav class="crumbs" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}">Главная</a>
                <span aria-hidden="true">/</span>
                <span>Контакты</span>
            </nav>

            <header class="flex items-start md:items-center gap-4 md:gap-6 flex-col md:flex-row mt-3">
                <div class="hero-ico" aria-hidden="true">@themeIcon('phone', 'w-5 h-5 opacity-80')</div>
                <div class="flex-1">
                    <h1 id="contacts-title" class="text-2xl md:text-3xl font-extrabold tracking-tight no-italic">
                        Связаться по спилу деревьев и вывозу манипулятором
                    </h1>
                    <p class="mt-2 text-sm md:text-base no-italic muted">
                        Курск и Курская область. Работаем <b>24/7</b>. Ответим, посоветуем метод и сориентируем по цене.
                    </p>
                    <div class="anchors" aria-label="Навигация по разделу">
                        <a class="chip" href="#details">Контакты</a>
                        <a class="chip" href="#map">Карта</a>
                        <a class="chip" href="#form">Форма</a>
                    </div>
                </div>
                <nav class="header-actions" aria-label="Быстрые действия">
                    <a href="tel:+79300373536" class="btn btn-primary" aria-label="Позвонить">@themeIcon('phone', 'w-4 h-4') <span
                            class="hidden sm:inline no-italic">Позвонить</span></a>
                    <a href="mailto:Suglobov2015@mail.ru" class="btn btn-outline"
                        aria-label="Написать на e-mail">@themeIcon('mail', 'w-4 h-4') <span
                            class="hidden sm:inline no-italic">E-mail</span></a>
                    <a href="https://vk.com/club228649931" target="_blank" rel="noopener" class="btn btn-outline"
                        aria-label="Написать во ВКонтакте">@themeIcon('send', 'w-4 h-4') <span
                            class="hidden sm:inline no-italic">VK</span></a>
                </nav>
            </header>
        </div>

        {{-- ===== Quick Cards ===== --}}
        <section id="details" class="grid sm:grid-cols-2 gap-3 md:gap-4 mb-4" aria-label="Контакты">
            <div class="card flex items-start gap-3">
                <div class="shrink-0 mt-0.5" aria-hidden="true">@themeIcon('phone', 'w-5 h-5 text-blue-600')</div>
                <div class="flex-1">
                    <div class="kv-label no-italic">Телефоны</div>
                    <div class="font-medium no-italic leading-relaxed">
                        <div><a href="tel:+79300373536" class="break-words">8 (930) 037-35-36</a></div>
                        <div class="muted">Городской: 305-008</div>
                    </div>
                    <div class="help mt-1 no-italic">Круглосуточно 24/7 <span id="openNow" class="ml-2">• <b>сейчас
                                доступно</b></span></div>
                </div>
                <button type="button" data-copy="+79300373536" class="btn btn-outline text-xs" title="Скопировать номер"
                    aria-label="Скопировать номер">@themeIcon('copy', 'w-4 h-4') <span class="no-italic">Копировать</span></button>
            </div>

            <div class="card flex items-start gap-3">
                <div class="shrink-0 mt-0.5" aria-hidden="true">@themeIcon('mail', 'w-5 h-5 text-blue-600')</div>
                <div class="flex-1">
                    <div class="kv-label no-italic">E-mail</div>
                    <div class="font-medium no-italic"><a href="mailto:Suglobov2015@mail.ru"
                            class="underline underline-offset-2 break-all">Suglobov2015@mail.ru</a></div>
                    <div class="help mt-1 no-italic">Отвечаем круглосуточно</div>
                </div>
                <button type="button" data-copy="Suglobov2015@mail.ru" class="btn btn-outline text-xs"
                    title="Скопировать e-mail" aria-label="Скопировать e-mail">@themeIcon('copy', 'w-4 h-4') <span
                        class="no-italic">Копировать</span></button>
            </div>

            <div class="card flex items-start gap-3">
                <div class="shrink-0 mt-0.5" aria-hidden="true">@themeIcon('map', 'w-5 h-5 text-blue-600')</div>
                <div class="flex-1">
                    <div class="kv-label no-italic">Регион</div>
                    <div class="font-medium no-italic">Курск и Курская область</div>
                    <div class="help no-italic">Выезд в соседние регионы — по договорённости</div>
                </div>
            </div>

            <div class="card flex gap-3">
                <div class="shrink-0 mt-0.5" aria-hidden="true">@themeIcon('clock', 'w-5 h-5 text-blue-600')</div>
                <div>
                    <div class="kv-label no-italic">График работы</div>
                    <div class="font-medium no-italic">Круглосуточно 24/7</div>
                    <div class="help no-italic">Без выходных</div>
                </div>
            </div>
        </section>

        {{-- ===== Map ===== --}}
        <section id="map" class="card mb-4" aria-label="Карта">
            <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                <h2 class="kv-label no-italic">Как нас найти</h2>
                <a class="btn btn-outline text-sm" target="_blank" rel="noopener"
                    href="https://yandex.ru/maps/?text=%D0%9A%D1%83%D1%80%D1%81%D0%BA%2C%20%D0%BF%D1%80%D0%BE%D1%81%D0%BF%D0%B5%D0%BA%D1%82%20%D0%92%D1%8F%D1%87%D0%B5%D1%81%D0%BB%D0%B0%D0%B2%D0%B0%20%D0%9A%D0%BB%D1%8B%D0%BA%D0%BE%D0%B2%D0%B0%2C%2073">
                    @themeIcon('map', 'w-4 h-4') Открыть в картах
                </a>
            </div>
            <div class="map-wrap"
                x-data="{ loaded: false }"
                x-init="loaded = sessionStorage.getItem('yandexMapConsent') === '1'">
                <template x-if="!loaded">
                    <button type="button" class="map-consent-placeholder"
                        @click="loaded = true; sessionStorage.setItem('yandexMapConsent', '1')"
                        style="width:100%; min-height:320px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem; background:var(--color-bg-muted,#f3f4f6); border:1px dashed var(--color-border,#d1d5db); border-radius:.5rem; cursor:pointer;">
                        <span aria-hidden="true">@themeIcon('map', 'w-8 h-8 text-blue-600')</span>
                        <span class="font-medium no-italic">Курск, проспект Вячеслава Клыкова, 73</span>
                        <span class="help no-italic">Показать карту (загрузит виджет Яндекс.Карт)</span>
                    </button>
                </template>
                <iframe x-show="loaded" x-cloak class="map-frame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    :src="loaded ? 'https://yandex.ru/map-widget/v1/?text=%D0%9A%D1%83%D1%80%D1%81%D0%BA%2C%20%D0%BF%D1%80%D0%BE%D1%81%D0%BF%D0%B5%D0%BA%D1%82%20%D0%92%D1%8F%D1%87%D0%B5%D1%81%D0%BB%D0%B0%D0%B2%D0%B0%20%D0%9A%D0%BB%D1%8B%D0%BA%D0%BE%D0%B2%D0%B0%2C%2073&z=16' : ''"
                    title="Курск, проспект Вячеслава Клыкова, 73"></iframe>
            </div>
        </section>

        {{-- ===== Links ===== --}}
        <section class="card mb-4" aria-label="Ссылки">
            <h2 class="kv-label no-italic mb-3">Мы в интернете</h2>
            <div class="social-list">
                <a href="{{ url('/about') }}" class="pill">@themeIcon('wallet', 'w-4 h-4') <span class="text-sm no-italic">О
                        компании</span></a>
                <a href="{{ url('/faq') }}" class="pill">@themeIcon('book', 'w-4 h-4') <span
                        class="text-sm no-italic">FAQ</span></a>
                <a href="/?category_ourworks=2" class="pill">@themeIcon('images', 'w-4 h-4') <span
                        class="text-sm no-italic">Выполненные работы</span></a>
                <a href="https://vk.com/club228649931" target="_blank" class="pill" rel="noopener">@themeIcon('send', 'w-4 h-4')
                    <span class="text-sm no-italic">ВКонтакте</span></a>
                <a href="{{ url('/page/prajs-list') }}" class="pill">@themeIcon('wallet', 'w-4 h-4') <span
                        class="text-sm no-italic">Прайс-лист</span></a>
            </div>
        </section>

        {{-- ===== Contact Form ===== --}}
        <section id="form" class="card" aria-label="Форма обратной связи">
            <h2 class="text-lg font-semibold mb-3 no-italic">Оставить заявку</h2>

            @if ($formAction)
                <form action="{{ $formAction }}" method="POST" class="grid gap-3 md:gap-4" novalidate
                    aria-describedby="formStatus">
                    @csrf
                    <input type="text" name="website" tabindex="-1" autocomplete="off"
                        style="position:absolute;left:-10000px;opacity:0" aria-hidden="true">

                    <div class="grid2">
                        <label class="field no-italic">
                            <span>Ваше имя</span>
                            <input type="text" name="name" required maxlength="120" class="input"
                                placeholder="Иван Иванов" autocomplete="name">
                            <span class="error" data-error-for="name"></span>
                        </label>
                        <label class="field no-italic">
                            <span>Телефон</span>
                            <input type="tel" name="phone" required pattern="^[0-9()+\-\s]{6,}$" inputmode="tel"
                                class="input" placeholder="8 930 037-35-36">
                            <span class="error" data-error-for="phone"></span>
                        </label>
                    </div>

                    <label class="field no-italic">
                        <span>E-mail (необязательно)</span>
                        <input type="email" name="email" class="input" placeholder="you@example.com"
                            autocomplete="email">
                        <span class="help">Нужен, если хотите дубль ответа на почту</span>
                    </label>

                    <label class="field no-italic">
                        <span>Сообщение</span>
                        <textarea name="message" rows="6" required class="input textarea"
                            placeholder="Кратко опишите задачу, адрес и удобные даты"></textarea>
                        <span class="error" data-error-for="message"></span>
                    </label>

                    <div class="grid2 items-start">
                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" name="agree" required class="mt-1" aria-describedby="agreeHelp">
                            <span class="no-italic">Согласен(на) на обработку персональных данных</span>
                        </label>
                        <div id="agreeHelp" class="text-xs help no-italic">Нажимая «Отправить», вы соглашаетесь с
                            политикой конфиденциальности.</div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="btn btn-primary" aria-label="Отправить сообщение">@themeIcon('send', 'w-4 h-4') <span
                                class="no-italic">Отправить</span></button>
                        <button type="reset" class="btn btn-outline">Сбросить</button>
                        <span id="formStatus" class="help" role="status" aria-live="polite"></span>
                    </div>
                </form>
            @else
                <div class="card" role="note">
                    Публичная форма пока не подключена. Напишите нам:
                    <a href="tel:+79300373536" class="underline">8 (930) 037-35-36</a>,
                    <a href="mailto:Suglobov2015@mail.ru" class="underline">Suglobov2015@mail.ru</a>
                    или во <a href="https://vk.com/club228649931" target="_blank" rel="noopener"
                        class="underline">ВКонтакте</a>.
                </div>
            @endif
        </section>

        {{-- ===== CTA-подвал ===== --}}
        <section class="page-wrap" style="padding:1.25rem 0 0">
            <div class="card" role="complementary" aria-label="Допомощь">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="m-0 muted">Нужно что то погрузить и вывезти? Пришлите фото в VK — оценим и предложим решение.
                    </p>
                    <div class="flex gap-2">
                        <a href="https://vk.com/club228649931" class="btn btn-outline" target="_blank"
                            rel="noopener">@themeIcon('send', 'w-4 h-4') VK</a>
                        <a href="tel:+79300373536" class="btn btn-primary">@themeIcon('phone', 'w-4 h-4') Позвонить</a>
                    </div>
                </div>
            </div>
        </section>
    </article>

    @push('scripts')
        <script>
            (function() {
                // --- Универсальное копирование текста ---
                function copiedUI(btn) {
                    const old = btn.innerHTML;
                    btn.innerHTML = `@themeIcon('check', 'w-4 h-4') <span class="no-italic">Скопировано</span>`;
                    btn.classList.add('bg-green-50', 'text-green-700');
                    setTimeout(() => {
                        btn.innerHTML = old;
                        btn.classList.remove('bg-green-50', 'text-green-700');
                    }, 1100);
                }

                async function copyText(text, btn) {
                    if (navigator.clipboard && window.isSecureContext) {
                        try {
                            await navigator.clipboard.writeText(text);
                            copiedUI(btn);
                            return;
                        } catch (e) {}
                    }
                    try {
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.readOnly = true;
                        ta.style.position = 'fixed';
                        ta.style.left = '-9999px';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        copiedUI(btn);
                        return;
                    } catch (e) {}
                    window.prompt('Скопируйте сочетанием Ctrl+C/⌘C и нажмите Enter', text);
                }

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-copy]');
                    if (!btn) return;
                    const val = (btn.getAttribute('data-copy') || '').trim();
                    if (!val) return;
                    copyText(val, btn);
                }, {
                    passive: true
                });

                // --- "Сейчас доступно": при графике 24/7 всегда показываем
                try {
                    const el = document.getElementById('openNow');
                    if (el) {
                        el.classList.remove('hidden');
                    }
                } catch (_) {}

                // Валидация формы (как было)
                const form = document.querySelector('form[action]');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        const required = [
                            ['name', 'Заполните имя'],
                            ['phone', 'Укажите телефон'],
                            ['message', 'Опишите задачу']
                        ];
                        let ok = true;
                        let firstInvalid = null;
                        required.forEach(([n, msg]) => {
                            const input = form.querySelector(`[name="${n}"]`);
                            const err = form.querySelector(`[data-error-for="${n}"]`);
                            if (err) err.textContent = '';
                            if (input && !input.value.trim()) {
                                ok = false;
                                firstInvalid ||= input;
                                if (err) err.textContent = msg;
                            }
                        });
                        const agree = form.querySelector('[name="agree"]');
                        if (agree && !agree.checked) {
                            ok = false;
                            alert('Нужно согласиться на обработку персональных данных');
                        }
                        if (!ok) {
                            e.preventDefault();
                            (firstInvalid || agree)?.focus();
                        } else {
                            const status = document.getElementById('formStatus');
                            if (status) status.textContent = 'Отправляем…';
                        }
                    });
                }
            })();
        </script>
    @endpush

    @push('head')
        {{-- Structured Data: TreeService (LocalBusiness) --}}
        <script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'TreeService',
  'name' => 'Спил46',
  'url' => url('/'),
  'areaServed' => 'Курск и Курская область',
  'sameAs' => ['https://vk.com/club228649931'],
  'telephone' => '+7-930-037-35-36',
  'email' => 'Suglobov2015@mail.ru',
  'openingHours' => ['Mo-Su 00:00-23:59'],
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => 'проспект Вячеслава Клыкова, 73',
    'addressLocality' => 'Курск',
    'addressRegion' => 'Курская область',
    'addressCountry' => 'RU'
  ],
  'contactPoint' => [[
    '@type' => 'ContactPoint',
    'telephone' => '+7-930-037-35-36',
    'contactType' => 'customer support',
    'areaServed' => 'RU',
    'availableLanguage' => ['ru']
  ]]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
    @endpush
@endsection
