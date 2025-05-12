<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Панель управления')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="bg-gray-100 text-gray-800 flex">
    @include('layouts.admin.sidebar')

    <div class="flex-1 flex flex-col">
        @include('layouts.admin.navbar')
        @include('layouts.admin.header')

        <main class="p-6 flex-1">
            @include('layouts.partials.flash')
            @yield('content')
        </main>

        @include('layouts.admin.footer')
    </div>

    {{-- Подключение JS для слайдшоу --}}
    <script src="{{ asset('admin-assets/js/slideshow.js') }}"></script>

    {{-- Подключение alpinejs --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Подключение доп. скриптов (например, TinyMCE) --}}
    @stack('scripts')
</body>

</html>
