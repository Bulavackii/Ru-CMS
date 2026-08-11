<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('install.title'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- 🎨 Tailwind CSS (локально) --}}
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">

    {{-- ⚡ Alpine.js для интерактивности (показ/скрытие пароля и т.д.) --}}
    <script defer src="{{ local_js('alpine.min.js') }}"></script>

    {{-- 🧭 Lucide — лёгкие line-иконки в духе SF Symbols, без единого
         обращения к CDN (вендорено локально в public/assets/js) --}}
    <script src="{{ local_js('lucide.min.js') }}"></script>

    <style>
        /*
         * Мастер установки — светлый «стеклянный» интерфейс (glassmorphism),
         * который целиком помещается во вьюпорт без вертикальной прокрутки.
         * Основной акцент интерфейса чёрно-белый (кнопки, текст), но на каждом
         * шаге добавлен свой цветной акцент (--accent): им подсвечивается аура
         * фона, полоска и иконка-бейдж карточки, фокус полей и активный шаг.
         * Цвет шага задаётся через @yield('accent') и живёт на .install-backdrop,
         * откуда наследуется вниз ко всем элементам карточки.
         *
         * Шрифтовой стек в духе macOS: на самой macOS/iOS -apple-system
         * резолвится в San Francisco без сети, на остальных платформах —
         * локально захостенный Inter.
         */
        :root {
            --accent: #6366f1;
        }
        :root, body {
            font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, ui-sans-serif, system-ui, sans-serif;
        }
        [x-cloak] { display: none !important; }

        /* ───────────────────────── Подложка ─────────────────────────────────
           Как на странице входа: спокойная сплошная поверхность из набора
           темы плюс одно неподвижное акцентное свечение сверху. Прежде здесь
           было «живое стекло» — два анимированных цветных пятна под
           полупрозрачной карточкой: на шаге, где вводят реквизиты базы,
           фон не должен шевелиться и тянуть на себя внимание. */
        .install-backdrop {
            position: relative;
            isolation: isolate;
            background: var(--color-bg, #f4f5fb);
        }
        .install-backdrop::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(760px 520px at 50% -12%,
                    color-mix(in srgb, var(--accent) 22%, transparent), transparent 68%),
                radial-gradient(520px 420px at 88% 108%,
                    color-mix(in srgb, var(--accent) 12%, transparent), transparent 66%);
        }

        /* ───────────────────────── Стеклянная карточка ─────────────────────── */
        .install-card {
            position: relative;
            background: var(--surface, #ffffff) !important;
            border: 1px solid var(--surface-bd, #e3e6ee) !important;
            box-shadow:
                0 1px 2px rgba(17, 24, 39, .04),
                0 18px 44px rgba(17, 24, 39, .10) !important;
        }
        /* Прямые края — тем же рубильником, что у карточки входа
           (body.au .au-card) и в панели (body.admin-sharp): перечислять
           скругления по вьюхам пришлось бы в каждом шаге мастера. */
        .install-card, .install-card * { border-radius: 0 !important }
        /* Тонкая акцентная полоска по верхней кромке карточки. */
        .install-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-top-left-radius: inherit;
            border-top-right-radius: inherit;
            background: linear-gradient(90deg,
                        var(--accent),
                        color-mix(in srgb, var(--accent) 55%, #ffffff),
                        var(--accent));
            z-index: 2;
        }

        /* ───────────────────────── Акцентный бейдж-иконка ──────────────────── */
        /* Градиент акцента + мягкое свечение вокруг. */
        .accent-badge {
            background: linear-gradient(140deg,
                        color-mix(in srgb, var(--accent) 92%, #fff 8%),
                        color-mix(in srgb, var(--accent) 72%, #000 12%)) !important;
            box-shadow:
                0 10px 22px -8px color-mix(in srgb, var(--accent) 65%, transparent),
                inset 0 1px 0 rgba(255,255,255,.4);
        }

        /* ───────────────────────── Подсказки-«сноски» ──────────────────────── */
        /* Сноска — как на странице входа: вторая поверхность темы, тонкая
           линия, акцент только в значке. Прежде это была стеклянная плашка
           с блюром, свечением и жирной акцентной гранью — три подряд такие
           сноски перетягивали внимание с того, что на шаге надо сделать. */
        .hint {
            position: relative;
            background: var(--surface-2, #f7f8fc);
            border: 1px solid var(--surface-bd, #e3e6ee);
            transition: border-color .2s ease, background .2s ease;
        }
        .hint:hover {
            border-color: color-mix(in srgb, var(--accent) 45%, var(--surface-bd, #e3e6ee));
        }
        /* Иконка-акцент: везде — в цвете шага. */
        .hint-ico { color: var(--accent); }
        /* Внутри плашки-сноски — ещё и в квадратном акцентном бейдже. */
        .hint .hint-ico {
            background: transparent;
            padding: 3px;
            box-sizing: content-box;
        }

        /* ───────────────────────── Кнопки ──────────────────────────────────── */
        /* Единый «острый» вид + микровзаимодействие при наведении. */
        .ui-btn {
            transition: transform .15s cubic-bezier(.16,1,.3,1), box-shadow .22s ease,
                        background-color .2s ease, border-color .2s ease;
        }
        .ui-btn:hover { transform: translateY(-2px); }
        .ui-btn:active { transform: translateY(0); }
        /* Основная тёмная кнопка: подсветка тенью цвета шага при наведении. */
        .ui-btn-primary { box-shadow: 0 12px 24px -12px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.14); }
        .ui-btn-primary:hover { box-shadow: 0 18px 34px -12px color-mix(in srgb, var(--accent) 55%, rgba(0,0,0,.45)); }

        /* ───────────────────────── Ровные углы 90° везде ───────────────────── */
        /* По просьбе — единый «острый» стиль без скруглений: карточки, кнопки,
           поля, чипы шагов, бейджи, плашки. Ловим любой элемент со скругляющей
           утилитой Tailwind (rounded-*) и обнуляем радиус. Пятна-ауры на фоне —
           это ::before/::after подложки, они правилом не затрагиваются и
           остаются круглыми. */
        .install-backdrop [class*="rounded"],
        .install-backdrop input,
        .install-backdrop textarea,
        .install-backdrop select {
            border-radius: 0 !important;
        }
        /* Цветная подсветка фокуса берётся из --accent. */
        .install-backdrop input:focus,
        .install-backdrop textarea:focus,
        .install-backdrop select:focus {
            border-color: var(--accent, #111827) !important;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent, #111827) 22%, transparent) !important;
            outline: none;
        }

        /* ───────────────────────── Кастомные тултипы ───────────────────────── */
        /* Элемент с data-tip="…" показывает стильную стеклянную подсказку. */
        [data-tip] { position: relative; }
        [data-tip]::after,
        [data-tip]::before {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 9px);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(4px);
            transition: opacity .16s ease, transform .16s ease, visibility .16s;
            pointer-events: none;
            z-index: 60;
        }
        [data-tip]::after {
            content: attr(data-tip);
            width: max-content;
            max-width: 15rem;
            padding: .4rem .6rem;
            border-radius: 0;
            background: rgba(17, 20, 32, 0.94);
            color: #f8fafc;
            font-size: 11px;
            line-height: 1.35;
            font-weight: 500;
            text-align: center;
            white-space: normal;
            box-shadow: 0 12px 28px -10px rgba(0,0,0,.55);
            border-top: 2px solid var(--accent);
        }
        [data-tip]::before {
            content: "";
            bottom: calc(100% + 3px);
            border: 6px solid transparent;
            border-top-color: rgba(17, 20, 32, 0.94);
        }
        [data-tip]:hover::after,
        [data-tip]:hover::before,
        [data-tip]:focus-visible::after,
        [data-tip]:focus-visible::before {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        /* Тултип снизу элемента: data-tip-pos="bottom" */
        [data-tip][data-tip-pos="bottom"]::after { bottom: auto; top: calc(100% + 9px); }
        [data-tip][data-tip-pos="bottom"]::before { bottom: auto; top: calc(100% + 3px); border-top-color: transparent; border-bottom-color: rgba(17,20,32,.94); }

        /* ───────────────────────── Прочее ──────────────────────────────────── */
        .animate-fade-in { animation: fadeIn .45s cubic-bezier(.16,1,.3,1); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px) scale(.99); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Компактный скроллбар для внутренних прокручиваемых областей карточек.
           overflow-x: hidden здесь обязателен: у элемента стоит overflow-y-auto,
           а по спецификации CSS, если одна ось не visible, вторая из visible
           превращается в auto. Без этой строки карточка получала ещё и
           горизонтальную полосу прокрутки, стоило тексту не влезть по ширине
           хотя бы на пиксель (ловилось на длинных русских и беларуских
           строках). Прокрутка внутри карточки задумана только вертикальной. */
        .install-scroll { overflow-x: hidden; scrollbar-width: thin; scrollbar-color: #cbd0dc transparent; }
        .install-scroll::-webkit-scrollbar { width: 6px; }
        .install-scroll::-webkit-scrollbar-thumb { background: #cbd0dc; border-radius: 999px; }
        .install-scroll::-webkit-scrollbar-track { background: transparent; }

        @media (prefers-reduced-motion: reduce) {
            .install-backdrop::before,
            .install-backdrop::after,
            .animate-fade-in { animation: none !important; }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full text-gray-900 antialiased">

{{--
    Каркас «всё во вьюпорте»: h-screen + flex-центрирование. Каждая страница
    отдаёт карточку с max-h, а длинный контент скроллится ВНУТРИ карточки
    (класс install-scroll), а не всей страницей. Цвет шага (--accent) задаётся
    страницей через @section('accent', '#hex') и живёт здесь, на подложке.
--}}
<div class="install-backdrop h-screen overflow-hidden" style="--accent: @yield('accent', '#6366f1')">
    <main class="h-full flex flex-col items-center justify-center px-4 sm:px-6 py-4 animate-fade-in">
        @if (session('install_notice'))
            <div class="install-card w-full max-w-xl mb-3 rounded-2xl px-4 py-2.5 text-sm text-gray-800 flex items-start gap-2 shrink-0">
                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 hint-ico"></i>
                <span>{{ session('install_notice') }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>

@stack('scripts')

</body>
</html>
