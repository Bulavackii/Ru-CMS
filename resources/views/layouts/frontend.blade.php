<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ✅ SEO мета-теги --}}
    <title>{{ $meta_title ?? ($title ?? 'RuShop CMS') }}</title>

    @if (!empty($meta_description))
        <meta name="description" content="{{ $meta_description }}">
    @endif

    @if (!empty($meta_keywords))
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif

    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- 🟦 Open Graph --}}
    <meta property="og:title" content="{{ $meta_title ?? ($title ?? 'RuShop CMS') }}">
    @if (!empty($meta_description))
        <meta property="og:description" content="{{ $meta_description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="ru_RU">

    {{-- 🐦 Twitter --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $meta_title ?? ($title ?? 'RuShop CMS') }}">
    @if (!empty($meta_description))
        <meta name="twitter:description" content="{{ $meta_description }}">
    @endif

    {{-- 🎨 Стили --}}
    @stack('styles')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite('resources/css/app.css')
</head>

<body class="relative text-gray-800 min-h-screen flex flex-col border-l border-r border-black overflow-x-hidden">

    {{-- 🖼️ Фоновое изображение (как в header) --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
        style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 📦 Весь остальной контент поверх --}}
    <div class="relative z-10 flex flex-col min-h-screen">

        {{-- 🔝 Верхняя панель --}}
        @include('layouts.partials.header')

        {{-- 🔔 Уведомления --}}
        <x-frontend-notifications />

        {{-- 📄 Контент страницы --}}
        <main class="flex-grow py-10">
            <div class="container mx-auto px-4">
                @yield('content')
            </div>
        </main>

        {{-- 📌 Подвал --}}
        @include('layouts.partials.footer')
    </div>

    {{-- 📜 JS --}}
    @stack('scripts')
</body>

</html>
