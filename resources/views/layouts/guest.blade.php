{{--
    Лейаут экранов входа, регистрации и восстановления пароля.

    Прежний был на четырнадцать строк: серый фон, белая коробка, и всё. Ни
    темы, ни шрифта, ни стеков для стилей и скриптов; язык страницы был прибит
    как «ru» независимо от локали. Смена оформления проекта эти экраны не
    затрагивала вовсе — они оставались синими, когда весь остальной сайт менял
    цвет.

    Здесь: две колонки — слева представление проекта, справа форма. На узком
    экране левая колонка убирается, остаётся форма. Цвета, шрифт и скругления
    берутся из активной темы тем же партиалом, что и на сайте.
--}}
@php
    // Тема сайта: тот же источник, что у layouts.frontend. Композер
    // ThemeServiceProvider отдаёт $activeTheme на каждую вьюху.
    $pageTheme = $siteTheme ?? $activeTheme ?? null;
    $themed = (bool) $pageTheme;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('frontend.auth.default_title')) — {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">

    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
    @include('layouts.partials.tw-compat')
    @include('layouts.partials.theme-head', ['pageTheme' => $pageTheme])

    <style>
        /*
         * Литеральный CSS: в сборке проекта нет opacity-модификаторов,
         * произвольных значений и половины цветов v3 (см. CLAUDE.md), а эти
         * экраны как раз держатся на полупрозрачности и градиенте.
         *
         * Всё цветное завязано на переменные темы — меняется оформление
         * проекта, меняются и эти страницы.
         */
        .au {
            --au-primary: var(--color-primary, #6366f1);
            --au-accent: var(--color-accent, #8b5cf6);
            --au-bg: var(--color-bg, #f4f5fb);
            --au-text: var(--color-text, #111827);
            --au-card: #ffffff;
            --au-line: #e3e6ee;
            --au-muted: #6b7280;
            --au-radius: var(--radius-md, 12px);

            min-height: 100vh;
            margin: 0;
            font-family: var(--font-base, -apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif);
            color: var(--au-text);
            background: var(--au-bg);
        }

        .au-wrap { display: grid; min-height: 100vh }
        @media (min-width: 1024px) { .au-wrap { grid-template-columns: 1.05fr 1fr } }

        /* ── Левая колонка: представление проекта ── */
        .au-aside {
            display: none;
            position: relative;
            flex-direction: column;
            justify-content: space-between;
            padding: 42px 46px;
            color: #fff;
            background: linear-gradient(140deg, var(--au-primary), var(--au-accent));
            overflow: hidden;
        }
        @media (min-width: 1024px) { .au-aside { display: flex } }

        /* Мягкое свечение поверх градиента — иначе заливка выглядит плоской.
           Пара кругов вместо картинки: ничего не грузится и не зависит от темы. */
        .au-aside::before,
        .au-aside::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .13);
            pointer-events: none;
        }
        .au-aside::before { width: 460px; height: 460px; top: -160px; right: -140px }
        .au-aside::after  { width: 320px; height: 320px; bottom: -120px; left: -90px }

        .au-brand { position: relative; display: flex; align-items: center; gap: 12px; font-size: 1.05rem; font-weight: 700 }
        .au-brand-mark {
            display: inline-flex; align-items: center; justify-content: center;
            width: 42px; height: 42px; font-size: 18px;
            background: rgba(255, 255, 255, .18); border-radius: var(--au-radius);
        }
        .au-brand img { max-height: 42px; width: auto }

        .au-lead { position: relative; max-width: 30rem }
        .au-lead h2 { margin: 0 0 12px; font-size: 2rem; font-weight: 800; line-height: 1.2 }
        .au-lead p { margin: 0; font-size: .98rem; line-height: 1.65; color: rgba(255, 255, 255, .88) }

        .au-points { position: relative; display: grid; gap: 12px; margin: 26px 0 0; padding: 0; list-style: none }
        .au-points li { display: flex; align-items: flex-start; gap: 11px; font-size: .9rem; line-height: 1.5 }
        .au-points i {
            display: inline-flex; align-items: center; justify-content: center;
            width: 24px; height: 24px; font-size: 11px; flex: 0 0 auto;
            background: rgba(255, 255, 255, .18); border-radius: 50%;
        }

        .au-aside-foot { position: relative; font-size: .78rem; color: rgba(255, 255, 255, .72) }
        .au-aside-foot a { color: #fff; text-decoration: underline }

        /* ── Правая колонка: форма ── */
        .au-main {
            display: flex; align-items: center; justify-content: center;
            padding: 32px 20px;
        }
        .au-card { width: 100%; max-width: 27rem }

        .au-top { margin-bottom: 22px }
        .au-back {
            display: inline-flex; align-items: center; gap: 7px; margin-bottom: 16px;
            font-size: .82rem; color: var(--au-muted); text-decoration: none;
        }
        .au-back:hover { color: var(--au-primary) }

        .au-title { margin: 0 0 6px; font-size: 1.6rem; font-weight: 800; line-height: 1.2 }
        .au-sub { margin: 0; font-size: .88rem; line-height: 1.6; color: var(--au-muted) }

        .au-box {
            padding: 24px;
            background: var(--au-card);
            border: 1px solid var(--au-line);
            border-radius: var(--au-radius);
            box-shadow: 0 12px 34px rgba(17, 24, 39, .07);
        }

        /* ── Поля ── */
        .au-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px }
        .au-label { font-size: .82rem; font-weight: 600 }
        .au-req { margin-left: 3px; color: #dc2626 }

        .au-input {
            width: 100%; padding: 11px 13px;
            font: inherit; font-size: .9rem; color: var(--au-text);
            background: #fff; border: 1px solid var(--au-line);
            border-radius: calc(var(--au-radius) - 4px);
            outline: none; transition: border-color .15s ease, box-shadow .15s ease;
        }
        .au-input:focus { border-color: var(--au-primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, .16) }
        .au-input::placeholder { color: #a8aebb }
        .au-input.is-bad { border-color: #dc2626 }

        .au-with-btn { position: relative }
        .au-with-btn .au-input { padding-right: 42px }
        .au-eye {
            position: absolute; right: 4px; top: 0; bottom: 0;
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; color: var(--au-muted); background: none; border: 0; cursor: pointer;
        }
        .au-eye:hover { color: var(--au-text) }

        .au-hint { font-size: .76rem; line-height: 1.5; color: var(--au-muted) }
        .au-err { font-size: .78rem; color: #dc2626 }

        .au-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 18px }
        .au-check { display: inline-flex; align-items: flex-start; gap: 8px; font-size: .84rem; cursor: pointer }
        .au-check input { margin-top: 2px; width: 15px; height: 15px; accent-color: var(--au-primary); flex: 0 0 auto }
        .au-link { color: var(--au-primary); font-size: .84rem; text-decoration: none }
        .au-link:hover { text-decoration: underline }

        .au-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            width: 100%; padding: 12px 20px;
            font: inherit; font-size: .92rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
            border: 0; border-radius: calc(var(--au-radius) - 4px);
            cursor: pointer; transition: filter .15s ease, transform .05s ease;
        }
        .au-btn:hover { filter: brightness(1.07) }
        .au-btn:active { transform: translateY(1px) }
        .au-btn[disabled] { opacity: .6; cursor: progress }

        .au-btn--ghost {
            color: var(--au-text); background: #fff; border: 1px solid var(--au-line);
        }
        .au-btn--ghost:hover { background: #f7f8fb; filter: none }

        .au-foot { margin-top: 18px; font-size: .86rem; text-align: center; color: var(--au-muted) }
        .au-foot a { color: var(--au-primary); font-weight: 600; text-decoration: none }
        .au-foot a:hover { text-decoration: underline }

        /* ── Сообщения ── */
        .au-note { display: flex; gap: 10px; margin-bottom: 16px; padding: 11px 13px;
                   font-size: .85rem; line-height: 1.5; border-radius: calc(var(--au-radius) - 4px) }
        .au-note--ok  { color: #14532d; background: #dcfce7; border: 1px solid #86efac }
        .au-note--bad { color: #7f1d1d; background: #fee2e2; border: 1px solid #fca5a5 }
        .au-note--info { color: #1e3a8a; background: #dbeafe; border: 1px solid #93c5fd }
        .au-note ul { margin: 5px 0 0; padding-left: 18px }

        /* ── Разделитель и группы ── */
        .au-split { display: flex; align-items: center; gap: 12px; margin: 18px 0; color: var(--au-muted); font-size: .76rem }
        .au-split::before, .au-split::after { content: ''; height: 1px; flex: 1 1 auto; background: var(--au-line) }

        .au-grid { display: grid; gap: 0 12px }
        @media (min-width: 520px) { .au-grid--2 { grid-template-columns: 1fr 1fr } }

        /* ── Сила пароля ── */
        .au-strength { display: flex; gap: 4px; margin-top: 7px }
        .au-strength span { height: 3px; flex: 1 1 auto; background: #e5e7eb; transition: background .2s ease }
        .au-strength.lv1 span:nth-child(1) { background: #dc2626 }
        .au-strength.lv2 span:nth-child(-n+2) { background: #f59e0b }
        .au-strength.lv3 span:nth-child(-n+3) { background: #eab308 }
        .au-strength.lv4 span { background: #16a34a }

        /* ── Тёмное оформление ── */
        @media (prefers-color-scheme: dark) {
            .au { --au-card: #171b24; --au-line: #333a49; --au-text: #e6e8ee; --au-muted: #9aa3b2 }
            .au-input { color: #e6e8ee; background: #10141c; border-color: #333a49 }
            .au-btn--ghost { color: #e6e8ee; background: #171b24 }
            .au-btn--ghost:hover { background: #1d222d }
            .au-strength span { background: #333a49 }
        }
    </style>

    @stack('styles')

    {{-- Alpine: в этом лейауте @vite нет, поэтому подключаем локальной
         сборкой — как во frontend-install. Двойной загрузки не возникает
         (та мина живёт в layouts/frontend, здесь её нет). --}}
    <script defer src="{{ local_js('alpine.min.js') }}"></script>
</head>
<body class="au {{ $themed ? 'fx-themed' : '' }}">
    <div class="au-wrap">

        {{-- ── Представление проекта ── --}}
        <aside class="au-aside">
            <a href="{{ url('/') }}" class="au-brand">
                @if($pageTheme && data_get($pageTheme->config, 'logo_url'))
                    <img src="{{ data_get($pageTheme->config, 'logo_url') }}" alt="{{ config('app.name') }}">
                @else
                    <span class="au-brand-mark"><i class="fas fa-layer-group"></i></span>
                @endif
                <span>{{ config('app.name') }}</span>
            </a>

            <div class="au-lead">
                <h2>@yield('aside_title', __('frontend.auth.aside_title'))</h2>
                <p>@yield('aside_text', __('frontend.auth.aside_text'))</p>

                <ul class="au-points">
                    <li><i class="fas fa-bolt"></i> <span>{{ __('frontend.auth.point_orders') }}</span></li>
                    <li><i class="fas fa-shield-halved"></i> <span>{{ __('frontend.auth.point_safe') }}</span></li>
                    <li><i class="fas fa-headset"></i> <span>{{ __('frontend.auth.point_support') }}</span></li>
                </ul>
            </div>

            <div class="au-aside-foot">
                &copy; {{ date('Y') }} {{ config('app.name') }} ·
                <a href="{{ url('/privacy') }}">{{ __('frontend.auth.privacy') }}</a>
            </div>
        </aside>

        {{-- ── Форма ── --}}
        <main class="au-main">
            <div class="au-card">
                <div class="au-top">
                    <a href="{{ url('/') }}" class="au-back">
                        <i class="fas fa-arrow-left"></i> {{ __('frontend.auth.to_site') }}
                    </a>
                    <h1 class="au-title">@yield('heading')</h1>
                    @hasSection('lead')
                        <p class="au-sub">@yield('lead')</p>
                    @endif
                </div>

                <div class="au-box">
                    @yield('content')
                </div>

                @hasSection('under')
                    <div class="au-foot">@yield('under')</div>
                @endif
            </div>
        </main>
    </div>

    <script>
        // Показ и скрытие пароля. Отдельного скрипта в проекте под это нет, а
        // поле пароля есть на четырёх экранах — держим здесь, в одном месте.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.au-eye');

            if (!button) {
                return;
            }

            var input = button.parentElement.querySelector('input');

            if (!input) {
                return;
            }

            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.querySelector('i').className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
            input.focus();
        });
    </script>

    @stack('scripts')
</body>
</html>
