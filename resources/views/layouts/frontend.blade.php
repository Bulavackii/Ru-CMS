<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Поддержка светлой и темной темы -->
    <meta name="color-scheme" content="light dark">

    <title>{{ $meta_title ?? ($title ?? 'RU CMS') }}</title>
    @if (!empty($meta_description))
        <meta name="description" content="{{ $meta_description }}">
    @endif
    @if (!empty($meta_keywords))
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg" sizes="120x120" href="{{ asset('favicon.svg') }}">

@if (config('seo.features.metrica') && config('seo.metrica.counter_id'))
    @php($metricaCounterId = (int) config('seo.metrica.counter_id'))
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id={{ $metricaCounterId }}', 'ym');

        ym({{ $metricaCounterId }}, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ $metricaCounterId }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
@endif

    {{-- OG/Twitter --}}
    <meta property="og:title" content="{{ $meta_title ?? ($title ?? 'RU CMS') }}">
    @if (!empty($meta_description))
        <meta property="og:description" content="{{ $meta_description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="ru_RU">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $meta_title ?? ($title ?? 'RU CMS') }}">
    @if (!empty($meta_description))
        <meta name="twitter:description" content="{{ $meta_description }}">
    @endif

    @stack('styles')

    {{-- Prism/Swiper/Tailwind/FA (локальные ресурсы) --}}
    <link href="{{ local_css('prism-tomorrow.min.css') }}" rel="stylesheet">
    <script src="{{ local_js('prism.min.js') }}"></script>
    <script src="{{ local_js('prism-markup.min.js') }}"></script>
    <script src="{{ local_js('prism-html.min.js') }}"></script>
    <script src="{{ local_js('prism-css.min.js') }}"></script>
    <script src="{{ local_js('prism-javascript.min.js') }}"></script>
    <script src="{{ local_js('prism-php.min.js') }}"></script>

    <link rel="stylesheet" href="{{ local_css('swiper-bundle.min.css') }}" />
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
    {{-- Фолбэк-иконки --}}
    <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Автоматическое определение системной темы --}}
    <script>
        (function() {
            const saved = localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (saved === null) {
                // Используем системную тему, если нет сохраненной
                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            } else if (saved === 'true') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
    <script defer src="{{ local_js('alpine.min.js') }}"></script>

    @php
        // ==== ТЕМА ====
        $tokens = $activeTheme->tokens ?? [];
        $config = $activeTheme->config ?? [];

        $fontBase = data_get($tokens, 'font.base', 'Inter, system-ui, sans-serif');
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

        $iconMode = data_get($config, 'icon_mode', 'fa');

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
            --bg-image: url('{{ $bgImage ?: asset('images/fon.jpg') }}');
        }

        .text-theme {
            color: var(--color-text)
        }

        .bg-theme {
            background: var(--color-bg)
        }

        .bg-header-theme {
            background: var(--color-header)
        }

        .bg-footer-theme {
            background: var(--color-footer)
        }

        .btn-theme {
            background: var(--color-primary);
            color: #fff
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
        #wrapper {
            transition: filter 0.3s ease;
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
</head>

<body class="relative text-gray-800 dark:text-gray-100 min-h-screen flex flex-col border-l border-r border-black dark:border-gray-700 overflow-x-hidden bg-white dark:bg-gray-900 transition-colors duration-200"
    style="font-family: var(--font-base, Inter, system-ui, sans-serif)">

    {{-- ЕДИНЫЙ фон-паттерн из темы --}}
    <div class="absolute inset-0 z-0 opacity-10 dark:opacity-5 pointer-events-none"
        style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto"></div>

    <div id="wrapper" class="relative z-10 flex flex-col min-h-screen">
        @include('layouts.partials.header')

        {{-- Модульное меню (позиция: header) --}}
        @include('Menu::frontend.header')

        <x-frontend-notifications />

        <main class="flex-grow py-6 sm:py-8 md:py-10">
            <div class="container mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
                @yield('content')
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
                   background:#fff;color:#111;font-size:26px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.25)">×</button>

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
                    style="display:inline-block;padding:.55rem .85rem;border-radius:.6rem;background:#fff;color:#111;
                text-decoration:none;font-weight:600;border:1px solid #e5e7eb;">Скачать</a>

                <button id="ru-lb-zi" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:#ffffff;color:#111;border:1px solid #e5e7eb;font-weight:600">+</button>
                <button id="ru-lb-zo" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:#ffffff;color:#111;border:1px solid #e5e7eb;font-weight:600">-</button>
                <button id="ru-lb-fit" type="button"
                    style="padding:.55rem .85rem;border-radius:.6rem;background:#ffffff;color:#111;border:1px solid #e5e7eb;font-weight:600">По
                    экрану</button>
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

</body>

</html>
