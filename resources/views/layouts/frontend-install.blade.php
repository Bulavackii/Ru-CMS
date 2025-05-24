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

    {{-- 🎨 Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- 🌐 Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-rp1xT+UO2Wg0f2zDfXYKmsd/4E1XKTX1W+YFYzAXMecxF2X7bm5l5Xp0yNsAxXr+1xOXT4kDAhNvqozJCRG+/g=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

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

</body>
</html>
