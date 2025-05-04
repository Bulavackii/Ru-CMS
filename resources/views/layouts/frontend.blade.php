<!DOCTYPE html>
<html lang="ru">

<head>
    @stack('styles')
    <meta charset="UTF-8">
    <title>@yield('title', 'RuShop CMS')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- tailwindcss -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    {{-- 🔝 Верхняя панель --}}
    @include('layouts.partials.header')

    {{-- 📄 Основной контент --}}
    <main class="flex-grow py-10">
        <div class="container mx-auto px-4">
            @yield('content')
        </div>
    </main>

    {{-- 📌 Подвал --}}
    @include('layouts.partials.footer')

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
