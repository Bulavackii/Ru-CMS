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

    {{-- 🟦 Open Graph для соцсетей --}}
    <meta property="og:title" content="{{ $meta_title ?? ($title ?? 'RuShop CMS') }}">
    @if (!empty($meta_description))
        <meta property="og:description" content="{{ $meta_description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="ru_RU">

    {{-- 🐦 Twitter-карты --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $meta_title ?? ($title ?? 'RuShop CMS') }}">
    @if (!empty($meta_description))
        <meta name="twitter:description" content="{{ $meta_description }}">
    @endif

    {{-- 🔗 Дополнительные стили (из шаблонов) --}}
    @stack('styles')

    {{-- Swiper CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    {{-- Tailwind CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-dY6zWyv..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    {{-- 🔝 Верхняя панель --}}
    @include('layouts.partials.header')

    {{-- 🔔 Уведомления --}}
    <x-frontend.notifications />

    {{-- 📄 Основной контент --}}
    <main class="flex-grow py-10">
        <div class="container mx-auto px-4">
            @yield('content')
        </div>
    </main>

    {{-- 📌 Подвал --}}
    @include('layouts.partials.footer')

    {{-- 🔽 Дополнительные скрипты (из шаблонов) --}}
    @stack('scripts')
</body>

</html>
