<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Установка Ru CMS')</title>
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
         * Мастер установки всегда светлый (без переключателя тёмной темы) —
         * это разовый, короткий флоу, где предсказуемость важнее персонализации.
         *
         * Шрифтовой стек в духе macOS: на самой macOS/iOS -apple-system и
         * BlinkMacSystemFont резолвятся в настоящий San Francisco БЕЗ единого
         * сетевого запроса (это системный шрифт ОС). На остальных платформах —
         * локально захостенный Inter (ближайший по начертанию, уже используется
         * во всём проекте).
         */
        :root, body {
            font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, ui-sans-serif, system-ui, sans-serif;
        }

        .animate-fade-in { animation: fadeIn .45s cubic-bezier(.16,1,.3,1); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Мягкая «Big Sur»-подложка: едва заметные размытые пятна на светлом фоне */
        .install-backdrop {
            background-color: #f5f6fa;
            background-image:
                radial-gradient(60rem 60rem at -10% -10%, rgba(59,130,246,.10), transparent 60%),
                radial-gradient(50rem 50rem at 110% 10%, rgba(99,102,241,.08), transparent 55%),
                radial-gradient(40rem 40rem at 50% 120%, rgba(14,165,233,.07), transparent 55%);
        }
    </style>

    @stack('styles')
</head>
<body class="h-full text-gray-900 antialiased">

<div class="install-backdrop min-h-screen">
    <main class="min-h-screen flex items-start justify-center p-4 sm:p-6 py-8 sm:py-12 animate-fade-in">
        <div class="w-full">
            @if (session('install_notice'))
                <div class="max-w-xl mx-auto mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ session('install_notice') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
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
