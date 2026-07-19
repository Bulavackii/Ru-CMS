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
         * Мастер установки всегда светлый, монохромный (чёрный/белый/серый —
         * никаких акцентных цветов) и обязан целиком помещаться во вьюпорт
         * без вертикальной прокрутки. Шрифтовой стек в духе macOS: на самой
         * macOS/iOS -apple-system резолвится в San Francisco без сети,
         * на остальных платформах — локально захостенный Inter.
         */
        :root, body {
            font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, ui-sans-serif, system-ui, sans-serif;
        }
        [x-cloak] { display: none !important; }

        .animate-fade-in { animation: fadeIn .4s cubic-bezier(.16,1,.3,1); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Монохромная подложка: лёгкое серое свечение, без цветных пятен */
        .install-backdrop {
            background-color: #f4f4f5;
            background-image:
                radial-gradient(55rem 55rem at -10% -10%, rgba(0,0,0,.05), transparent 60%),
                radial-gradient(45rem 45rem at 110% 15%, rgba(0,0,0,.04), transparent 55%),
                radial-gradient(40rem 40rem at 50% 120%, rgba(0,0,0,.03), transparent 55%);
        }

        /* Компактный скроллбар для внутренних прокручиваемых областей карточек */
        .install-scroll { scrollbar-width: thin; scrollbar-color: #d4d4d8 transparent; }
        .install-scroll::-webkit-scrollbar { width: 6px; }
        .install-scroll::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 999px; }
        .install-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>

    @stack('styles')
</head>
<body class="h-full text-gray-900 antialiased">

{{--
    Каркас «всё во вьюпорте»: h-screen + flex-центрирование. Каждая страница
    отдаёт карточку с max-h, а длинный контент скроллится ВНУТРИ карточки
    (класс install-scroll), а не всей страницей.
--}}
<div class="install-backdrop h-screen overflow-hidden">
    <main class="h-full flex flex-col items-center justify-center px-4 sm:px-6 py-4 animate-fade-in">
        @if (session('install_notice'))
            <div class="w-full max-w-xl mb-3 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 flex items-start gap-2 shadow-sm shrink-0">
                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
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
