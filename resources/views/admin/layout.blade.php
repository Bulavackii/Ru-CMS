<!DOCTYPE html>
<html lang="ru" class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Админка')</title>

    {{-- 💡 Tailwind CSS --}}
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">

    {{-- 🎨 Font Awesome --}}
    <link href="{{ local_css('font-awesome/all.min.css') }}" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- ⚙️ Custom Head --}}
    @stack('head')
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white antialiased transition-colors duration-200 ease-in-out">

    {{-- 📦 Контейнер --}}
    <div class="container max-w-7xl mx-auto px-4 py-8">

        {{-- 🧭 Навигация (если включена) --}}
        @includeIf('admin.partials.nav')

        {{-- 📄 Контент --}}
        @yield('content')

    </div>

    {{-- 🌙 Тема / уведомления / скрипты --}}
    @stack('scripts')
</body>
</html>
