<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $meta_title ?? ($title ?? 'RU CMS') }}</title>

    @if (!empty($meta_description))
        <meta name="description" content="{{ $meta_description }}">
    @endif
    @if (!empty($meta_keywords))
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif

    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    {{-- 🟦 Open Graph --}}
    <meta property="og:title" content="{{ $meta_title ?? ($title ?? 'RU CMS') }}">
    @if (!empty($meta_description))
        <meta property="og:description" content="{{ $meta_description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="ru_RU">

    {{-- 🐦 Twitter --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $meta_title ?? ($title ?? 'RU CMS') }}">
    @if (!empty($meta_description))
        <meta name="twitter:description" content="{{ $meta_description }}">
    @endif

    @stack('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite('resources/css/app.css')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

        .accessibility-button {
            filter: none !important;
            isolation: isolate;
        }
    </style>
</head>

<body class="relative text-gray-800 min-h-screen flex flex-col border-l border-r border-black overflow-x-hidden">

    {{-- 🖼️ Фон --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
        style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 📦 Контент в wrapper для фильтров --}}
    <div id="wrapper" class="relative z-10 flex flex-col min-h-screen">

        @include('layouts.partials.header')
        <x-frontend-notifications />

        <main class="flex-grow py-10">
            <div class="container mx-auto px-4">
                @yield('content')
            </div>
        </main>

        @include('layouts.partials.footer')
    </div>

    {{-- ♿ Спецвозможности — за пределами wrapper, чтобы не фильтровались --}}
    @if (!empty($accessibility) && $accessibility->enabled)
        @include('Accessibility::frontend.widget', ['settings' => $accessibility])
    @endif

    {{-- ⬆️ Кнопка "наверх" вне wrapper, чтобы не фильтровалась --}}
    <div class="scroll-to-top-container">
        @includeIf('components.scroll-to-top')
    </div>

    @stack('scripts')
    <script src="{{ asset('js/accessibility.js') }}"></script>
</body>

</html>
