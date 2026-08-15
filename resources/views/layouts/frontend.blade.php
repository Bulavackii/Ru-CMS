<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Поддержка светлой и темной темы -->
    <meta name="color-scheme" content="light dark">

    @php
        // Данные раздела «SEO — страницы», если для этого адреса есть запись.
        // Их подставляет SeoServiceProvider (композер лейаута); когда записи
        // нет — всё как раньше: значения из самой новости/страницы.
        $seoMeta = $seoMeta ?? null;
        $seoTitle = $seoMeta['title'] ?? null;
        $seoDescription = $seoMeta['description'] ?? null;
        $seoKeywords = $seoMeta['keywords'] ?? null;

        $pageTitle = $seoTitle ?: ($meta_title ?? ($title ?? 'RU CMS'));
        $pageDescription = $seoDescription ?: ($meta_description ?? null);
        $pageKeywords = $seoKeywords ?: ($meta_keywords ?? null);
        $pageCanonical = $seoMeta['canonical'] ?? null ?: url()->current();
        $pageRobots = $seoMeta['robots'] ?? null ?: 'index, follow';
        $pageOg = $seoMeta['og'] ?? [];
    @endphp

    <title>{{ $pageTitle }}</title>
    @if (!empty($pageDescription))
        <meta name="description" content="{{ $pageDescription }}">
    @endif
    @if (!empty($pageKeywords))
        <meta name="keywords" content="{{ $pageKeywords }}">
    @endif
    <meta name="robots" content="{{ $pageRobots }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    <link rel="icon" type="image/svg" sizes="120x120" href="{{ asset('favicon.svg') }}">

@if (config('seo.features.metrica') && config('seo.metrica.counter_id'))
    @php
        $metricaCounterId = (int) config('seo.metrica.counter_id');
        // Согласие на счётчики. Cookie ставит баннер «Уведомления» с типом
        // cookie; ключ тот же, что у него в поле «Ключ cookie».
        $analyticsConsent = request()->cookie('ru_consent') === '1';
    @endphp
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        // Счётчик вынесен в функцию и НЕ запускается сам.
        //
        // Раньше он подключался при каждой отрисовке страницы, то есть данные
        // посетителя уходили наружу до того, как он что-либо разрешил. Теперь
        // запуск происходит либо здесь (если согласие уже дано в этом сеансе),
        // либо из баннера согласия сразу после нажатия «Принять» — без
        // перезагрузки, чтобы не потерять первый просмотр.
        window.ruStartAnalytics = function () {
            if (window.ruAnalyticsStarted) { return; }
            window.ruAnalyticsStarted = true;

            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id={{ $metricaCounterId }}', 'ym');

            ym({{ $metricaCounterId }}, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
        };

        @if ($analyticsConsent)
            window.ruStartAnalytics();
        @endif
    </script>
    @if ($analyticsConsent)
        {{-- Пиксель без скриптов — тоже обращение наружу, поэтому и он за согласием. --}}
        <noscript><div><img src="https://mc.yandex.ru/watch/{{ $metricaCounterId }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    @endif
    <!-- /Yandex.Metrika counter -->
@endif

    {{-- OG/Twitter: значения из раздела SEO, иначе прежние --}}
    <meta property="og:title" content="{{ $pageOg['og:title'] ?? $pageTitle }}">
    @if (!empty($pageOg['og:description'] ?? $pageDescription))
        <meta property="og:description" content="{{ $pageOg['og:description'] ?? $pageDescription }}">
    @endif
    <meta property="og:url" content="{{ $pageOg['og:url'] ?? $pageCanonical }}">
    <meta property="og:type" content="{{ $pageOg['og:type'] ?? 'article' }}">
    @php
        // og:locale был жёстко ru_RU независимо от выбранного языка
        $ogLocaleMap = [
            'ru' => 'ru_RU', 'en' => 'en_US', 'be' => 'be_BY', 'kk' => 'kk_KZ',
            'de' => 'de_DE', 'fr' => 'fr_FR', 'it' => 'it_IT',
        ];
        $currentLocale = app()->getLocale();
    @endphp
    <meta property="og:locale" content="{{ $ogLocaleMap[$currentLocale] ?? 'ru_RU' }}">

    {{-- hreflang: та же страница на других доступных языках.
         Ссылка ведёт на переключатель — язык хранится в сессии, отдельных
         URL у локалей нет. --}}
    @foreach(available_locales() as $altLocale)
        @if($altLocale !== $currentLocale)
            <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ route('frontend.locale.set', $altLocale) }}">
        @endif
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
    @if (!empty($pageOg['og:image']))
        <meta property="og:image" content="{{ $pageOg['og:image'] }}">
    @endif
    <meta name="twitter:card" content="{{ $pageOg['twitter:card'] ?? 'summary' }}">
    <meta name="twitter:title" content="{{ $pageOg['twitter:title'] ?? $pageTitle }}">
    @if (!empty($pageOg['twitter:description'] ?? $pageDescription))
        <meta name="twitter:description" content="{{ $pageOg['twitter:description'] ?? $pageDescription }}">
    @endif
    @if (!empty($pageOg['twitter:image']))
        <meta name="twitter:image" content="{{ $pageOg['twitter:image'] }}">
    @endif

    @if (!empty($seoMeta['jsonld']))
        {{-- JSON-LD из раздела SEO. Схема задаётся администратором, поэтому
             выводим через json_encode с экранированием тегов. --}}
        <script type="application/ld+json">{!! json_encode($seoMeta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif

    @stack('styles')

    {{-- Prism/Swiper/Tailwind/FA (локальные ресурсы) --}}
    <link href="{{ local_css('prism-tomorrow.min.css') }}" rel="stylesheet">

    {{-- Оформление содержимого материалов — ОДИН файл на три места: сайт,
         страницы раздела «Страницы» и рамка редактора в панели. Именно
         поэтому блок в редакторе выглядит так же, как у посетителя.

         ⚠️ Раньше он подключался ТОЛЬКО во вьюхе страницы. Новости его не
         получали вовсе: блоки содержимого (pc-grid, pc-stats, pc-cta),
         которые редактор предлагает кнопкой «Блоки» и в новостях тоже,
         выводились на сайте без оформления — голым списком. Заметили это,
         когда блок кода в уроке оказался светлым в редакторе и тёмным на
         сайте.

         ⚠️ Идёт ПОСЛЕ Prism намеренно: у обоих правила на pre, и при равной
         силе побеждает объявленное позже. Раньше блок кода красила тема
         подсветки, из-за чего фон отличался от того, что показывал редактор.

         asset_v, а не asset: без метки версии браузер держит прежнюю копию,
         и правка оформления не доезжает до посетителя. --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/content-blocks.css') }}">
    <script src="{{ local_js('prism.min.js') }}"></script>
    <script src="{{ local_js('prism-markup.min.js') }}"></script>
    <script src="{{ local_js('prism-html.min.js') }}"></script>
    <script src="{{ local_js('prism-css.min.js') }}"></script>
    <script src="{{ local_js('prism-javascript.min.js') }}"></script>
    <script src="{{ local_js('prism-php.min.js') }}"></script>

    <link rel="stylesheet" href="{{ local_css('swiper-bundle.min.css') }}" />
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
    @include('layouts.partials.tw-compat')
    {{-- Переменные поверхностей: один набор на сайт и на страницы входа. --}}
    @include('layouts.partials.theme-surfaces')
    {{-- Фолбэк-иконки --}}
    <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Свой проигрыватель звука в материалах: родной выглядит в каждом
         браузере по-своему. Без скрипта остаются родные кнопки — разметка
         рабочая сама по себе. --}}
    <script src="{{ asset_v('assets/js/content-players.js') }}" defer></script>

    {{-- Гарнитуры, которые владелец может выбрать в редакторе материала.
         Один файл на все двадцать одну: сам woff2 браузер скачивает только для
         той, что реально встретилась в тексте. Без этой строки выбранный в
         панели шрифт подменялся бы у посетителя системным — редактор обещал бы
         одно, а сайт показывал другое. --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/content-fonts.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Тёмный режим на сайте больше не отдельный переключатель: его роль
         играет тема «Графит» из модуля Темы (её можно выбрать в шапке).
         Прежний флаг darkMode из localStorage вычищаем, иначе у тех, кто
         успел его включить, класс .dark остался бы навсегда — выключать
         его стало нечем. В админке тёмный режим не трогаем, он свой. --}}
    <script>
        (function() {
            try { localStorage.removeItem('darkMode'); } catch (e) {}
            document.documentElement.classList.remove('dark');
        })();
    </script>
    

    @php
        // ==== ТЕМА ====
        // На сайте показываем тему, выбранную посетителем (переключатель в
        // шапке); если он ничего не выбирал или выбранную тему удалили —
        // активную тему сайта. $siteTheme приходит из VisualServiceProvider.
        $pageTheme = $siteTheme ?? $activeTheme ?? null;

        $tokens = $pageTheme->tokens ?? [];
        $config = $pageTheme->config ?? [];

        $fontBase = data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
        $radiusMd = data_get($tokens, 'radius.md', '12px');

        $cBg = data_get($tokens, 'colors.bg', '#ffffff');
        $cText = data_get($tokens, 'colors.text', '#111827');
        $cPrimary = data_get($tokens, 'colors.primary', '#2563eb');
        $cAccent = data_get($tokens, 'colors.accent', '#10b981');
        $cHeader = data_get($tokens, 'colors.header', '#ffffff');
        $cFooter = data_get($tokens, 'colors.footer', '#ffffff');

        $bgImage =
            data_get($config, 'background_url') ??
            (data_get($config, 'bg_url') ??
                (data_get($config, 'pattern_url') ?? (data_get($config, 'bg_image') ?? null)));

        $iconMode = data_get($config, 'icon_mode', 'lucide');

        $fontProvider = data_get($config, 'font_provider'); // 'local' | 'google' | 'bunny' | null
        $fontName = trim((string) data_get($config, 'font_name', ''));

        $localFontSlug = null;
        if ($fontProvider === 'local' && $fontName !== '') {
            $slug = \Illuminate\Support\Str::slug($fontName);
            $localFontSlug = array_key_exists($slug, LOCAL_FONTS) ? $slug : null;
        }
    @endphp

    {{-- Шрифт: локальный (по умолчанию — Inter), без обращений к внешним CDN --}}
    @if ($localFontSlug)
        <link rel="stylesheet" href="{{ local_font_css($localFontSlug) }}">
    @elseif ($fontProvider === 'google' && $fontName !== '')
        <link
            href="https://fonts.googleapis.com/css2?family={{ urlencode($fontName) }}:wght@400;500;600;700&display=swap"
            rel="stylesheet">
    @elseif($fontProvider === 'bunny' && $fontName !== '')
        <link
            href="https://fonts.bunny.net/css?family={{ urlencode(str_replace(' ', '-', $fontName)) }}:400,500,600,700"
            rel="stylesheet">
    @else
        <link rel="stylesheet" href="{{ local_font_css('inter') }}">
    @endif

    {{-- Иконки по режиму (локальные) --}}
    @php
        $iconAsset = theme_icon_asset($iconMode);
    @endphp
    @if($iconAsset)
        @if($iconMode === 'lucide')
            <script src="{{ $iconAsset }}"></script>
        @else
            <link rel="stylesheet" href="{{ $iconAsset }}">
        @endif
    @endif

    {{-- CSS-переменные темы + единый bg-image --}}
    <style id="theme-vars">
        :root {
            --font-base: {{ $fontBase }};
            --radius-md: {{ $radiusMd }};
            --color-bg: {{ $cBg }};
            --color-text: {{ $cText }};
            --color-primary: {{ $cPrimary }};
            --color-accent: {{ $cAccent }};
            --color-header: {{ $cHeader }};
            --color-footer: {{ $cFooter }};
            --bg-image: url('{{ $bgImage ?: asset('images/fon.svg') }}');
        }

        .text-theme {
            color: var(--color-text)
        }

        .bg-theme {
            background: var(--color-bg)
        }

        /* Фон и текст страницы берутся из активной темы. Класс fx-themed
           навешивается только когда тема выбрана, поэтому без темы страница
           выглядит ровно как раньше. !important нужен, чтобы перекрыть
           bg-white/text-gray-800 — Tailwind-классы на самом <body>. */
        body.fx-themed {
            background: var(--color-bg) !important;
            color: var(--color-text) !important;
        }

        /* Тёмные темы: стеклянные карточки и подложки дизайн-слоя светлые по
           умолчанию — на тёмном фоне они давали белые пятна с тёмным текстом. */
        body.fx-theme-dark .fx-card {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(255, 255, 255, .10);
            color: var(--color-text);
        }

        body.fx-theme-dark .fx-section-title,
        body.fx-theme-dark .fx-card h1,
        body.fx-theme-dark .fx-card h2,
        body.fx-theme-dark .fx-card h3,
        body.fx-theme-dark .fx-card p,
        body.fx-theme-dark .fx-card span:not(.fx-chip):not(.fx-badge) {
            color: var(--color-text);
        }

        body.fx-theme-dark .fx-section-sub {
            color: color-mix(in srgb, var(--color-text) 70%, transparent);
        }


        /* Заголовки и приглушённый текст набраны классами, у которых в
           основе светлая тема: тёмный текст на светлом фоне. Тёмный режим у
           этих классов свой и отзывается на .dark — системную тему браузера,
           а не на тему сайта. Из-за этого на тёмной теме заголовки разделов
           оставались чёрными на чёрном.

           Внутри карточек и кнопок цвет уже задан выше, поэтому здесь
           перечислены только заголовки и общие классы текста. */
        body.fx-theme-dark h1,
        body.fx-theme-dark h2,
        body.fx-theme-dark h3,
        body.fx-theme-dark h4,
        body.fx-theme-dark h5,
        body.fx-theme-dark h6,
        body.fx-theme-dark .text-gray-900,
        body.fx-theme-dark .text-gray-800,
        body.fx-theme-dark .text-gray-700 {
            color: var(--color-text);
        }

        body.fx-theme-dark .text-gray-600,
        body.fx-theme-dark .text-gray-500,
        body.fx-theme-dark .text-gray-400 {
            color: color-mix(in srgb, var(--color-text) 68%, transparent);
        }

        /* Заливки, набранные готовыми классами. У них есть тёмный вариант, но
           он отзывается на .dark — тёмный режим СИСТЕМЫ, а не тему сайта.
           Отсюда светлые прямоугольники посреди тёмной страницы: панель
           покупки под товаром, кнопки счётчика, служебные врезки.

           Перечислять их по одному в каждом шаблоне бессмысленно — классов
           наперечёт, а мест десятки. */
        body.fx-theme-dark .bg-white,
        body.fx-theme-dark .bg-gray-50,
        body.fx-theme-dark .bg-gray-100,
        body.fx-theme-dark .bg-slate-50,
        body.fx-theme-dark .bg-slate-100 {
            background-color: var(--surface);
        }

        body.fx-theme-dark .bg-gray-200,
        body.fx-theme-dark .bg-slate-200 {
            background-color: var(--surface-2);
        }

        /* Разделители рисуются светло-серым — на тёмном фоне их не видно
           вовсе, и блоки слипаются в одно полотно. */
        body.fx-theme-dark .border-gray-200,
        body.fx-theme-dark .border-gray-100 {
            border-color: color-mix(in srgb, var(--color-text) 14%, transparent);
        }

        .bg-header-theme {
            background: var(--color-header)
        }

        .bg-footer-theme {
            background: var(--color-footer)
        }

        .btn-theme {
            background: var(--color-primary);
            color:var(--on-accent,#fff)
        }

        .rounded-theme {
            border-radius: var(--radius-md)
        }

        .rounded,
        .rounded-md,
        .rounded-lg,
        .rounded-xl,
        .rounded-2xl {
            border-radius: var(--radius-md) !important;
        }

        button,
        input,
        .card {
            border-radius: var(--radius-md)
        }
    </style>

    <style>
        /* Переход filter убран: он держал значение на нулевом кадре, и
           режимы спецвозможностей (сепия, монохром) вычислялись как
           sepia(0)/grayscale(0) — класс стоял, а картинка не менялась. */
        #wrapper {
            transition: none;
        }

        .accessibility-button,
        .scroll-to-top {
            position: fixed;
            z-index: 9999;
        }

        .accessibility-button {
            bottom: 1.5rem;
            left: 1.5rem;
            filter: none !important;
            isolation: isolate;
        }

        .scroll-to-top {
            bottom: 1.5rem;
            right: 1.5rem;
        }

        .scroll-to-top-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            filter: none !important;
            backdrop-filter: none !important;
            isolation: isolate;
        }

        /* ========= Поддержка темной темы ========= */
        :root {
            color-scheme: light dark;
        }
    </style>

    {{-- ===== Единый дизайн-слой фронтенда (стиль админки/Install) ===== --}}
    <style>
        /* Акцент дизайн-слоя берётся из активной темы (--color-primary/accent
           объявлены выше в #theme-vars). Литералы оставлены как значения по
           умолчанию: без темы страница выглядит ровно как раньше. Раньше эти
           цвета были прибиты гвоздями, и смена темы не меняла ни кнопки, ни
           полосы, ни бейджи. */
        :root{
            --fx-a: var(--color-primary, #6366f1);
            --fx-a2: var(--color-accent, #8b5cf6);
            --fx-a-ink: var(--color-primary, #4338ca);
            --fx-grad: linear-gradient(135deg, var(--color-primary, #6366f1), var(--color-accent, #8b5cf6));
            --fx-bar: linear-gradient(90deg, var(--color-primary, #6366f1), var(--color-accent, #8b5cf6), var(--color-primary, #ec4899));

            /* Полупрозрачные производные акцента (тени, подложки чипов).
               Первое объявление — запасное на случай, если браузер не знает
               color-mix; второе перекрывает его там, где функция поддержана. */
            --fx-a-soft: color-mix(in srgb, var(--color-primary, #6366f1) 12%, transparent);
            --fx-a-soft: color-mix(in srgb, var(--color-primary, #6366f1) 12%, transparent);
            --fx-a-edge: color-mix(in srgb, var(--color-primary, #6366f1) 35%, transparent);
            --fx-a-edge: color-mix(in srgb, var(--color-primary, #6366f1) 35%, transparent);
            --fx-a-glow: color-mix(in srgb, var(--color-primary, #6366f1) 65%, transparent);
            --fx-a-glow: color-mix(in srgb, var(--color-primary, #6366f1) 65%, transparent);
        }
        /* Тонкая градиентная акцент-полоса (верх страницы, над подвалом, у секций) */
        /* Подложка сайта. Насыщенность живёт внутри самого SVG — здесь
           только фиксация при прокрутке и приглушение в тёмной теме,
           иначе светлый узор бьёт по глазам. */
        .fx-bg-layer{ opacity:1; }
        .fx-theme-dark .fx-bg-layer,
        .dark .fx-bg-layer{ opacity:.18; }

        /* Узор нарисован в indigo, а тема может быть мятной или
           терракотовой. Перекрашиваем слоем поверх: mix-blend-mode:color
           берёт цвет отсюда, а светлоту — у самого узора, поэтому
           геометрия и глубина сохраняются. */
        .fx-bg-layer::after{
            content:''; position:absolute; inset:0;
            background:linear-gradient(135deg,
                var(--color-primary, #6366f1),
                var(--color-accent, #8b5cf6));
            mix-blend-mode:color; opacity:.85; pointer-events:none;
        }
        .fx-theme-dark .fx-bg-layer::after,
        .dark .fx-bg-layer::after{ opacity:.7; }

        .fx-topbar{ height:3px; background:var(--fx-bar); }
        .fx-accent-bar{ height:3px; width:100%; background:var(--fx-bar); border:0; border-radius:2px; }
        /* Градиентный бейдж-иконка у заголовков (как .admin-icon-badge) */
        .fx-badge{ width:2.5rem; height:2.5rem; border-radius:.75rem; display:inline-flex; align-items:center;
            justify-content:center; background:var(--fx-grad); color:#fff; flex:0 0 auto;
            box-shadow:0 10px 24px -10px var(--fx-a-glow); }
        .fx-badge svg,.fx-badge i{ width:1.3rem; height:1.3rem; font-size:1.3rem; line-height:1; }
        /* Стеклянная карточка */
        .fx-card{ background:rgba(255,255,255,.82); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px);
            border:1px solid rgba(17,24,39,.08); border-radius:16px; box-shadow:0 1px 2px rgba(17,24,39,.05);
            transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .fx-card:hover{ transform:translateY(-3px); box-shadow:0 22px 44px -22px var(--fx-a-glow);
            border-color:var(--fx-a-edge); }
        :root.dark .fx-card{ background:rgba(15,23,42,.72); border-color:rgba(255,255,255,.08); }
        /* Indigo-кнопка (градиент) */
        .fx-btn{ display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            background:var(--fx-grad); color:#fff; font-weight:500; border-radius:10px; text-decoration:none;
            box-shadow:0 10px 22px -12px var(--fx-a-glow); transition:filter .15s ease, transform .15s ease; }
        .fx-btn:hover{ filter:brightness(1.07); transform:translateY(-1px); color:#fff; }
        /* Чип-пилюля */
        .fx-chip{ display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .6rem; border-radius:999px;
            background:var(--fx-a-soft); color:var(--fx-a-ink); font-size:.72rem; font-weight:500; line-height:1.5; }
        :root.dark .fx-chip{ background:var(--fx-a-soft); color:var(--fx-a2); }
        /* Заголовок секции */
        .fx-section-title{ font-weight:600; letter-spacing:-.01em; color:var(--surface-ink,#111827); }

        /* Плашка под заголовком секции.
           На светлом фоне заголовок читался, но стоит владельцу поставить
           свою фоновую картинку — тёмную или пёструю — и текст пропадал.
           Белая подложка с прямыми углами, в стиле остального фронта. */
        .fx-section-head{ display:inline-flex; align-items:center; gap:.75rem;
            padding:.7rem 1.15rem; background:var(--surface,#fff); border:1px solid rgba(17,24,39,.08);
            box-shadow:0 2px 10px rgba(15,23,42,.06); max-width:100%; }
        :root.dark .fx-section-head{ background:#111827; border-color:rgba(255,255,255,.1); }
        :root.dark .fx-section-title{ color:#f3f4f6; }
        .fx-section-sub{ font-size:.85rem; color:var(--surface-mute,#6b7280); }
        :root.dark .fx-section-sub{ color:#9ca3af; }
        /* ── Плашка заголовка раздела: ОДИН вид на все шаблоны ──────────
           Каждый шаблон объявлял её сам (.nw__head, .mag__head, .gm__head,
           .clinic__head, .pr__head, .pg__head) — шесть почти одинаковых
           копий, которые уже разъехались отступами и тенью. Здесь один
           набор на всех; селектор с body, потому что копии в шаблонах
           попадают в тот же стек стилей и при равной силе перебили бы.
           (Название директивы стека здесь НЕ пишем: Blade выполняет
           директивы даже внутри комментария — см. CLAUDE.md.)

           Что изменилось по виду: слева акцентная полоса вместо ровной
           рамки со всех сторон (тот же приём, что у карточек и в панели),
           надзаголовок моноширинным капсом — как на страницах входа и в
           корзине, — и подложка чуть плотнее, чтобы плашка читалась на
           фоновой картинке темы. */
        body .nw__head, body .mag__head, body .gm__head,
        body .clinic__head, body .pr__head, body .pg__head,
        body .fx-section-head{
            display:inline-flex; align-items:center; gap:.85rem;
            box-sizing:border-box; max-width:100%;
            padding:.65rem 1.1rem .65rem .95rem;
            background:var(--surface,#fff);
            border:1px solid var(--surface-bd,rgba(17,24,39,.08));
            border-left:3px solid var(--color-primary,#6366f1);
            box-shadow:0 6px 20px -12px rgba(15,23,42,.35);
            margin-bottom:1.35rem;
        }
        /* Название раздела и строка под ним. Название чуть меньше прежнего
           (1.5rem на телефоне переносилось в две строки), строка под ним —
           моноширинным капсом: она поясняющая, и так не спорит с названием. */
        body .nw__title, body .mag__title, body .gm__title,
        body .clinic__title, body .pr__title, body .pg__title,
        body .fx-section-head .fx-section-title{
            margin:0; font-size:1.35rem; font-weight:700; line-height:1.2;
            letter-spacing:-.015em; color:var(--surface-ink,#111827);
        }
        body .nw__sub, body .mag__sub, body .gm__sub,
        body .clinic__sub, body .pr__sub, body .pg__sub,
        body .fx-section-head .fx-section-sub{
            margin:.15rem 0 0;
            font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size:.68rem; letter-spacing:.06em; text-transform:uppercase;
            color:var(--surface-mute,#6b7280);
        }
        /* Значок раздела — один размер у всех плашек. */
        body .nw__head .fx-badge, body .mag__head .fx-badge,
        body .gm__head .fx-badge, body .clinic__head .fx-badge,
        body .pr__head .fx-badge, body .pg__head .fx-badge,
        body .fx-section-head .fx-badge,
        body .gm__badge, body .clinic__badge, body .mag__badge{
            width:2.35rem; height:2.35rem; flex:none;
        }

        @media (max-width: 1024px), (max-height: 500px){
            /* inline-flex по содержимому на узком экране выглядел случайным
               ярлыком у левого края, а длинная поясняющая строка распирала
               плашку за край. Во всю ширину — и то и другое уходит. */
            body .nw__head, body .mag__head, body .gm__head,
            body .clinic__head, body .pr__head, body .pg__head,
            body .fx-section-head{
                display:flex; width:100%; padding:.6rem .85rem .6rem .7rem;
                margin-bottom:1rem;
            }
            body .nw__title, body .mag__title, body .gm__title,
            body .clinic__title, body .pr__title, body .pg__title,
            body .fx-section-head .fx-section-title{ font-size:1.15rem }
            body .nw__sub, body .mag__sub, body .gm__sub,
            body .clinic__sub, body .pr__sub, body .pg__sub{ font-size:12px }
            body .nw__head .fx-badge, body .mag__head .fx-badge,
            body .gm__head .fx-badge, body .clinic__head .fx-badge,
            body .pr__head .fx-badge, body .pg__head .fx-badge,
            body .fx-section-head .fx-badge,
            body .gm__badge, body .clinic__badge, body .mag__badge{ width:2.1rem; height:2.1rem }
        }

        .fx-ico{ color:var(--fx-a); }
        /* Заглушка «Нет изображения» — в тон редизайну (мягкий indigo-градиент) */
        .fx-noimg{ width:100%; height:100%; display:flex; flex-direction:column; align-items:center;
            justify-content:center; gap:.45rem; background:linear-gradient(135deg,#eef2ff,#faf5ff); }
        :root.dark .fx-noimg{ background:linear-gradient(135deg,#1e1b4b,#312e81); }
        .fx-noimg .fx-noimg-ico{ font-size:2.1rem; color:#a5b4fc; }
        :root.dark .fx-noimg .fx-noimg-ico{ color:var(--fx-a); }
        .fx-noimg span{ font-size:.72rem; font-weight:500; letter-spacing:.04em; color:var(--color-primary, #818cf8); text-transform:uppercase; }
        /* Красивое hover-подчёркивание для пунктов меню (хедер/футер/сайдбар).
           Цвет — из ТЕМЫ (--color-primary/--color-accent, меняются в модуле Тем). */
        .fx-underline{ position:relative; }
        .fx-underline::after{ content:''; position:absolute; left:.45rem; right:.45rem; bottom:1px; height:2px;
            border-radius:2px; background:var(--color-primary,#6366f1);
            transform:scaleX(0); transform-origin:center; transition:transform .22s ease; }
        .fx-underline:hover::after,
        .fx-underline.active-link::after{ transform:scaleX(1); }
        /* Ровные углы на всём фронтенде (как в админке): универсальный !important
           перебивает и Tailwind-скругления, и литеральный CSS с border-radius.
           На SVG-геометрию (rx) не влияет — флаги и иконки без rx, им и не нужно. */
        body.fx-sharp, body.fx-sharp *{ border-radius:0 !important; }

        /* ═════════ Телефоны и планшеты в портрете ═════════
           Плавающие кнопки («Наверх» справа, «Спецвозможности» слева) прибиты
           к низу окна и занимают нижние ~72 пикселя. На узком экране под ними
           оказывалась последняя строка подвала — копирайт и версия. Даём
           странице запас снизу, чтобы её содержимое до них не доходило. */
        @media (max-width: 1024px), (max-height: 500px){
            /* ⚠️ Кнопке освобождаем УГОЛ, а не полосу во всю ширину.
               Сначала запас был отступом у страницы — полоса легла ниже
               подвала, вне его фона. Потом тот же запас внутри подвала —
               полоса осталась, просто с фоном: после последней строки
               зияло 78 пикселей пустоты.
               На самом деле кнопка узкая (48) и стоит в правом углу, а
               нижняя строка подвала центрирована. Достаточно подвинуть её
               содержимое влево от угла — и пустая полоса не нужна вовсе. */
            /* Запаса СНИЗУ здесь нет намеренно: он был последним следом
               прежнего подхода и давал 36 пикселей пустоты под последней
               строкой подвала (замер на 896×414: содержимое кончалось на
               3384, подвал — на 3420). Кнопка узкая и стоит в углу, а угол
               освобождён вбок строкой ниже — полоса не нужна. */
            body .f-meta-row{ padding-right:4rem; }

            /* ── Плавающие кнопки — в один угол ───────────────────────────
               Кнопка спецвозможностей стояла внизу СЛЕВА и на любой
               прокрутке лежала на тексте: строка начинается от левого края,
               и 48 пикселей поверх неё съедают первое слово. Справа край
               строки рваный, поэтому обе кнопки собираем там — столбиком,
               «Наверх» снизу, спецвозможности над ней.
               Селектор с body: собственные правила виджета объявлены позже
               этого блока и при равной силе перебили бы его. */
            /* На телефоне плавающая кнопка остаётся ОДНА — спецвозможности,
               в самом углу. «Наверх» убрана: на узком экране две кнопки
               занимают целую колонку у правого края и ложатся на органы
               управления (в корзине — прямо на «+» у количества), а быстрый
               возврат наверх телефон и так даёт — жестом и тапом по строке
               состояния. На десктопе обе остаются как были. */
            body .a11y-fab{ left:auto; right:1rem; bottom:1rem; }
            body #backToTopBtn{ display:none; }

            /* Пока страницу листают — кнопки уходят с дороги.
               На узком экране плавающая кнопка неизбежно ложится на
               содержимое: слева это первое слово строки, справа — кнопка
               «+» у количества товара в корзине. Прятать её насовсем
               нельзя (спецвозможности должны быть под рукой), поэтому она
               исчезает на время прокрутки и возвращается, когда палец
               остановился. Класс ставит небольшой скрипт ниже. */
            body .a11y-fab{ transition:opacity .18s ease, transform .18s ease; }
            body.fx-scrolling .a11y-fab{
                opacity:0; transform:translateY(.75rem); pointer-events:none;
            }

            /* Пока раскрыто меню шапки — плавающая кнопка убирается.
               В альбомной ориентации телефона экран высотой 414, раскрытое
               меню занимает его целиком, и кнопка виджета садится ровно на
               кнопку «Искать» в нижней строке меню: замер показывал
               перекрытие, а на экране от кнопки оставалось «Иска».
               Прятать её здесь безопасно — виджет открывается с любой
               страницы, а меню закрывается одним нажатием. */
            body.hdr-nav-open .a11y-fab{
                opacity:0; transform:translateY(.75rem); pointer-events:none;
            }

            /* ── Зоны нажатия ──────────────────────────────────────────────
               Палец накрывает примерно 9 мм, поэтому Apple и Google просят
               не меньше 44 и 48 точек; WCAG 2.5.8 (AA) ставит нижнюю границу
               в 24. Замер на iPhone XR нашёл 105 элементов ниже 44: кнопки
               количества были 32×40, «Подробнее» — 87×19, метки категорий —
               89×22, крестик уведомления — 26×26.

               Главным действиям даём полные 44. Метки категорий оставляем
               компактными, но выше порога AA: растянуть их до 44 значило бы
               превратить подпись в кнопку и сломать вид карточек. */
            .pg-card__more, .nw-card__more, .clinic-card__link, .pr-meta__link,
            .pr-card__cart, .mag-card__link{
                display:inline-flex; align-items:center; min-height:44px;
            }
            .pr-qty__btn{ min-width:44px; min-height:44px; }
            .pr-qty__input{ height:44px; }
            .notif-close{ width:44px; height:44px; }

            .pg-chip, .nw-chip, .pr-chip, .mag-chip{
                display:inline-flex; align-items:center; min-height:32px; padding:.25rem .6rem;
            }

            /* ── Нижняя граница размера текста ─────────────────────────────
               Метки категорий шли по 10.5–11 пикселей. На бумаге это мелко,
               на телефоне в руке — нечитаемо, а браузер ещё и предлагает
               «увеличить», ломая вёрстку. Двенадцать — общепринятый минимум
               для вспомогательных подписей. */
            .pg-chip, .nw-chip, .pr-chip, .mag-chip,
            .f-brand-chip, .f-social-label, .pr-card__stock,
            .fx-chip, .pc-now, .pr-badge{ font-size:12px; }

            /* Личный кабинет: подписи сводки и списков описаний шли по
               9,9–10,9. Отбор по куску имени класса — тот же приём, что в
               панели: подписи называются одинаково во всех вьюхах кабинета,
               а перечисление поштучно устаревает с первой новой страницей. */
            /* ⚠️ Префикс body обязателен: у вьюх кабинета есть свои правила
               вида .acc-link .acc-state, и при равной силе побеждают они —
               их <style> идёт ниже по документу. */
            body .acc-sum__label, body .acc-socials__label,
            body .acc-state, body .acc-link .acc-state,
            body .acc-list dt, body .cart-ico__badge,
            body [class*="acc-"] [class*="label"],
            body [class*="eyebrow"]{ font-size:12px }

            /* Ссылки внутри текста кабинета — почта в списке профиля и
               «Заполнить» в подсказке: 18–19 пикселей в высоту. */
            body .acc-list a, body .acc-fill-hint a{
                display:inline-block; min-height:24px }

            /* Хлебные крошки — навигация, а не подпись: по ним возвращаются
               на раздел выше. Были 24 пикселя в высоту. */
            /* Селектор по aria-label, а не по классам: у крошек нет своего
               класса, они собраны из утилит, и цепляться за них — значит
               ломаться при первой же правке разметки. Подпись же обязана
               быть на месте: без неё крошки недоступны с экранного диктора. */
            nav[aria-label="Хлебные крошки"] a{ display:inline-flex; align-items:center; min-height:44px }

            /* Чипы стека в подвале (PHP, Laravel, «Исходники») — 95×27. */
            .f-brand-chip{ min-height:32px; display:inline-flex; align-items:center }

            /* Заголовок карточки — главная цель нажатия, а был 23 пикселя
               высотой: в списке из шести карточек попасть в нужную трудно. */
            .pg-card__title a, .nw-card__title a,
            .clinic-card__title a, .pr-card__title a, .mag-card__title a{
                display:flex; align-items:center; min-height:44px;
            }

            /* Плашка про cookie: крестик 26×26 и кнопки 36 в высоту.
               Селекторы с родителем — стили плашки объявлены ПОЗЖЕ этого
               блока и при равной силе перебивали бы его. */
            .notif-stack .notif-close{ width:44px; height:44px; }
            .notif-stack .notif-consent__yes,
            .notif-stack .notif-consent__no{ min-height:44px; }

            /* Точки слайдера были 38×4 — попасть в такую полоску нельзя.
               Растим саму зону нажатия, а видимую черту оставляем тонкой. */
            .swiper-pagination-bullet{ position:relative; height:6px; }
            .swiper-pagination-bullet::after{
                content:''; position:absolute; left:0; right:0; top:50%;
                height:32px; transform:translateY(-50%);
            }
        }
    </style>
    {{-- Просмотр картинки во весь экран — общий для всего сайта: страницы,
         новости, любые шаблоны. Раньше он жил внутри шаблона слайдшоу и
         работал только там, а в остальных местах клик по картинке
         перехватывали расширения браузера. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/image-viewer.css') }}">
</head>

@php
    // Признак тёмной темы считает хелпер theme_is_dark(): тот же расчёт нужен
    // макету входа, а второй экземпляр этой арифметики неминуемо разошёлся бы
    // с первым — в этом проекте так уже случалось не раз.
    $themeIsDark = theme_is_dark($cBg);
@endphp

<body class="fx-sharp relative text-gray-800 dark:text-gray-100 min-h-screen flex flex-col overflow-x-hidden bg-white dark:bg-gray-900 transition-colors duration-200 {{ $pageTheme ? 'fx-themed' : '' }} {{ $themeIsDark ? 'fx-theme-dark' : '' }}"
    style="font-family: var(--font-base, -apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif)">

    {{-- ЕДИНЫЙ фон-паттерн из темы --}}
    <div class="fixed inset-0 z-0 pointer-events-none fx-bg-layer"
        style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto"></div>

    <div id="wrapper" class="relative z-10 flex flex-col min-h-screen">
        {{-- Верхняя градиентная акцент-полоса (единый стиль с админкой/Install) --}}
        <div class="fx-topbar"></div>

        {{-- 🧩 Зона фрагмента: полоса объявления НАД шапкой. Пусто/выключено —
             не выводится ничего, вёрстка страницы не меняется. --}}
        @php $fragmentTopbar = \Modules\Visual\Support\FragmentRenderer::zone('frontend.topbar'); @endphp
        @if($fragmentTopbar)
            <div class="fragment-zone fragment-zone--topbar">{!! $fragmentTopbar !!}</div>
        @endif

        @include('layouts.partials.header')

        {{-- 🧩 Зона фрагмента: сразу под шапкой сайта --}}
        @php $fragmentHeader = \Modules\Visual\Support\FragmentRenderer::zone('frontend.header'); @endphp
        @if($fragmentHeader)
            <div class="fragment-zone fragment-zone--header">{!! $fragmentHeader !!}</div>
        @endif


        {{-- Модульное меню (позиция header) теперь встроено в саму шапку
             (layouts.partials.header) одной строкой с поиском — отдельного
             бара здесь больше нет, чтобы не было двух навигационных полос. --}}

        {{-- Меню позиции sidebar — выдвижная боковая панель (кнопка у левого края).
             Показывается только если sidebar-меню заполнено. --}}
        @include('Menu::frontend.sidebar')

        <x-frontend-notifications />

        <main class="flex-grow py-4 sm:py-6 md:py-8">
            <div class="container mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
                @yield('content')

                {{-- 🧩 Зона фрагмента: под содержимым страницы --}}
                @php $fragmentContentBottom = \Modules\Visual\Support\FragmentRenderer::zone('frontend.content.bottom'); @endphp
                @if($fragmentContentBottom)
                    <div class="fragment-zone fragment-zone--content-bottom mt-8">{!! $fragmentContentBottom !!}</div>
                @endif
            </div>
        </main>

        @include('layouts.partials.footer')


    </div>

    @if (!empty($accessibility) && $accessibility->enabled)
        @include('Accessibility::frontend.widget', ['settings' => $accessibility])
    @endif

    <div class="scroll-to-top-container">
        @includeIf('components.scroll-to-top')
    </div>

    @stack('scripts')
    <script src="{{ asset('js/accessibility.js') }}"></script>
    
    {{-- Обработка ошибок загрузки ресурсов --}}
    <script>
      (function() {
        // Проверка загрузки Alpine.js
        window.addEventListener('load', function() {
          if (typeof Alpine === 'undefined') {
            console.warn('Alpine.js не загружен. Проверьте путь к файлу.');
          }
        });
        
        // Обработка ошибок загрузки скриптов и стилей
        document.addEventListener('error', function(e) {
          if (e.target.tagName === 'SCRIPT') {
            console.error('Ошибка загрузки скрипта:', e.target.src);
          } else if (e.target.tagName === 'LINK' && e.target.rel === 'stylesheet') {
            console.error('Ошибка загрузки стилей:', e.target.href);
          }
        }, true);
      })();
    </script>

    @if ($iconMode === 'lucide')
        <script>
            document.addEventListener('DOMContentLoaded', () => window.lucide && window.lucide.createIcons());
        </script>
    @endif

    <!-- Фолбэк для пустых/битых иконок -->
    <script>
        (function() {
            const FALLBACK_CLASS = 'fa-solid fa-circle-question';

            function swapToFallback(el) {
                const cls = (el.getAttribute('class') || '').trim();
                el.outerHTML = `<i class="${FALLBACK_CLASS} ${cls}" data-theme-icon data-fallback="1"></i>`;
            }
            // если какая-то иконка (webfont/lucide) не заняла размеры — считаем, что она не нашлась
            function fix(root = document) {
                root.querySelectorAll('[data-theme-icon]').forEach(el => {
                    const r = el.getBoundingClientRect();
                    if ((r.width === 0 || r.height === 0) && !el.hasAttribute('data-fallback')) swapToFallback(
                        el);
                });
            }
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                try {
                    window.lucide.createIcons();
                    setTimeout(() => fix(), 50);
                } catch (_) {}
            }
            window.addEventListener('load', () => fix());
        })();
    </script>
    {{-- === LIGHTBOX c zoom: универсальный === --}}
    <div id="ru-lb" style="position:fixed;inset:0;display:none;z-index:9999;">
        <div data-close="1"
            style="position:absolute;inset:0;background:rgba(0,0,0,.75);backdrop-filter:saturate(1.1) blur(2px)"></div>

        <figure
            style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
                 margin:0;display:flex;flex-direction:column;align-items:center;gap:.5rem;">
            <button data-close="1" type="button"
                style="position:absolute;right:-12px;top:-12px;width:42px;height:42px;border:0;border-radius:50%;
                   background:var(--surface,#fff);color:var(--surface-ink,#111);font-size:26px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.25)">×</button>

            <!-- Сцена: скроллим/таскаем, внутри меняем ширину картинки по scale -->
            <div id="ru-lb-stage"
                style="width:min(96vw,1200px);height:82vh;background:#0b0b0b;border-radius:.75rem;overflow:auto;
                display:flex;align-items:center;justify-content:center;cursor:auto;">
                <img id="ru-lb-img" alt=""
                    style="display:block;max-width:none;height:auto;user-select:none;-webkit-user-drag:none;border-radius:.4rem;box-shadow:0 6px 24px rgba(0,0,0,.25)">
            </div>

            <figcaption id="ru-lb-cap"
                style="color:#e5e7eb;font-size:.9rem;text-align:center;max-width:80ch;line-height:1.35"></figcaption>

            <!-- Панель действий -->
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;justify-content:center">
                <a id="ru-lb-dl" href="#" download
                    style="display:inline-block;padding:.55rem .85rem;border-radius:.6rem;background:var(--surface,#fff);color:var(--surface-ink,#111);
                text-decoration:none;font-weight:600;border:1px solid var(--surface-bd,#e5e7eb);">{{ __('frontend.common.download') }}</a>

                <button id="ru-lb-zi" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:var(--surface,#ffffff);color:var(--surface-ink,#111);border:1px solid var(--surface-bd,#e5e7eb);font-weight:600">+</button>
                <button id="ru-lb-zo" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:var(--surface,#ffffff);color:var(--surface-ink,#111);border:1px solid var(--surface-bd,#e5e7eb);font-weight:600">-</button>
                <button id="ru-lb-fit" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:var(--surface,#ffffff);color:var(--surface-ink,#111);border:1px solid var(--surface-bd,#e5e7eb);font-weight:600">{{ __('frontend.common.fit_screen') }}</button>
                <span id="ru-lb-zoomval" style="color:#e5e7eb;font-size:.9rem;margin-left:.25rem">100%</span>
            </div>
        </figure>
    </div>

    <script>
        (function() {
            const lb = document.getElementById('ru-lb');
            const stage = document.getElementById('ru-lb-stage');
            const img = document.getElementById('ru-lb-img');
            const cap = document.getElementById('ru-lb-cap');
            const dlnk = document.getElementById('ru-lb-dl');
            const zi = document.getElementById('ru-lb-zi');
            const zo = document.getElementById('ru-lb-zo');
            const fitB = document.getElementById('ru-lb-fit');
            const zval = document.getElementById('ru-lb-zoomval');

            let natW = 0,
                natH = 0; // натуральные размеры изображения
            let scale = 1; // текущий масштаб (1 = натуральный)
            let fit = 1; // масштаб "по экрану" (contain)
            const MAX = 6; // максимум 600% от натурального
            const MIN = 0.2; // минимум 20% от натурального
            const STEP = 1.25; // множитель зума

            function applyScale(center = true) {
                // меняем реальную ширину изображения — скролл работает честно
                img.style.width = Math.round(natW * scale) + 'px';
                img.style.height = 'auto';
                zval.textContent = Math.round(scale * 100) + '%';
                // ставим курсор «ладонь», когда масштаб больше fit
                stage.style.cursor = (scale > fit + 0.01) ? 'grab' : 'auto';

                if (center) {
                    // центрируем картинку в сцене
                    const cx = (img.clientWidth - stage.clientWidth) / 2;
                    const cy = (img.clientHeight - stage.clientHeight) / 2;
                    stage.scrollLeft = Math.max(0, cx);
                    stage.scrollTop = Math.max(0, cy);
                }
            }

            function computeFit() {
                if (!natW || !natH) return 1;
                const sw = stage.clientWidth,
                    sh = stage.clientHeight;
                return Math.min(sw / natW, sh / natH);
            }

            function openLB(src, alt, filename) {
                if (!src) return;
                img.onload = () => {
                    natW = img.naturalWidth;
                    natH = img.naturalHeight;
                    fit = computeFit();
                    scale = Math.max(fit, Math.min(1, 1)); // стартуем: по экрану, но не больше 100%
                    applyScale(true);
                };
                img.src = src;
                img.alt = alt || '';
                cap.textContent = alt || '';
                dlnk.href = src;
                try {
                    const url = new URL(src, location.origin);
                    dlnk.setAttribute('download', filename || url.pathname.split('/').pop() || 'image');
                } catch {
                    dlnk.setAttribute('download', 'image');
                }

                lb.style.display = 'block';
                document.documentElement.style.overflow = 'hidden';
            }

            function closeLB() {
                lb.style.display = 'none';
                img.src = '';
                document.documentElement.style.overflow = '';
            }

            // Кнопки зума
            zi.addEventListener('click', () => {
                scale = Math.min(MAX, scale * STEP);
                applyScale(false);
            });
            zo.addEventListener('click', () => {
                scale = Math.max(MIN, scale / STEP);
                applyScale(false);
            });
            fitB.addEventListener('click', () => {
                fit = computeFit();
                scale = fit;
                applyScale(true);
            });

            // Колесо мыши — зум (с Ctrl или без, чтобы удобнее)
            stage.addEventListener('wheel', (e) => {
                if (lb.style.display !== 'block') return;
                e.preventDefault();
                const k = (e.deltaY < 0) ? STEP : (1 / STEP);
                scale = Math.max(MIN, Math.min(MAX, scale * k));
                applyScale(false);
            }, {
                passive: false
            });

            // Перетаскивание (панорамирование)
            let drag = false,
                sx = 0,
                sy = 0,
                sl = 0,
                st = 0;
            stage.addEventListener('mousedown', e => {
                if (scale <= fit + 0.01) return;
                drag = true;
                sx = e.pageX;
                sy = e.pageY;
                sl = stage.scrollLeft;
                st = stage.scrollTop;
                stage.style.cursor = 'grabbing';
                e.preventDefault();
            });
            window.addEventListener('mouseup', () => {
                drag = false;
                if (scale > fit + 0.01) stage.style.cursor = 'grab';
            });
            window.addEventListener('mousemove', e => {
                if (!drag) return;
                stage.scrollLeft = sl - (e.pageX - sx);
                stage.scrollTop = st - (e.pageY - sy);
            });

            // Закрытие
            lb.addEventListener('click', e => {
                if (e.target.dataset.close) closeLB();
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && lb.style.display === 'block') closeLB();
            });

            // Подхватываем клики по всем «контентным» картинкам на сайте
            const SELECTOR = '.news-content img, .page-content img, .slideshow img, article img, main img';

            function bindAll() {
                document.querySelectorAll(SELECTOR).forEach(el => {
                    if (el.dataset.lbBound || el.closest('.no-zoom')) return;
                    el.dataset.lbBound = '1';
                    el.style.cursor = 'zoom-in';
                    el.addEventListener('click', () => {
                        const full = el.getAttribute('data-full') || el.currentSrc || el.src;
                        openLB(full, el.getAttribute('alt') || '', el.getAttribute('data-filename') ||
                            null);
                    });
                });
            }
            document.addEventListener('DOMContentLoaded', bindAll);
            window.addEventListener('load', bindAll);
            new MutationObserver(bindAll).observe(document.body, {
                subtree: true,
                childList: true
            });
        })();
    </script>

    <script src="{{ asset('assets/js/image-viewer.js') }}" defer></script>

    {{-- Плавающие кнопки уходят с дороги, пока страницу листают.
         На узком экране им негде встать, чтобы ни на что не лечь: слева
         это первое слово каждой строки, справа — кнопка «+» у количества
         товара в корзине. Прятать насовсем нельзя, поэтому прячем только
         на время прокрутки: палец остановился — кнопки вернулись.
         Само правило показа живёт в стилях (`body.fx-scrolling`). --}}
    <script>
        (function () {
            var body = document.body, timer = null;

            // Только там, где кнопки реально мешают: на широком экране они
            // стоят в пустом поле сбоку и ничего не перекрывают.
            if (!window.matchMedia('(max-width: 1024px), (max-height: 500px)').matches) return;

            window.addEventListener('scroll', function () {
                if (!body.classList.contains('fx-scrolling')) {
                    body.classList.add('fx-scrolling');
                }
                clearTimeout(timer);
                timer = setTimeout(function () {
                    body.classList.remove('fx-scrolling');
                }, 450);
            }, { passive: true });
        })();
    </script>
</body>

</html>
