<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Установка Ru CMS')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- 🌙 Применяем dark-mode до загрузки Tailwind --}}
    <script>
        (function () {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    {{-- 🎨 Tailwind CSS (локально) --}}
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">

    {{-- 🌐 Font Awesome --}}
    <link rel="stylesheet"
          href="{{ local_css('font-awesome/all.min.css') }}"
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- ⚡ Alpine.js для интерактивности (показ/скрытие пароля и т.д.) --}}
    <script defer src="{{ local_js('alpine.min.js') }}"></script>

    {{-- 💫 Анимации --}}
    <style>
        .animate-fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">

{{-- 📦 Контент --}}
<main class="min-h-screen flex items-center justify-center p-6 animate-fade-in">
    @yield('content')
</main>

{{-- 🌗 Скрипт переключения темы --}}
<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }
</script>

{{-- ✅ Проверка загрузки Alpine.js --}}
<script>
    window.addEventListener('load', function() {
        if (typeof Alpine === 'undefined') {
            console.warn('Alpine.js не загружен. Функция показа/скрытия пароля может не работать.');
        }
    });
</script>

</body>
</html>
