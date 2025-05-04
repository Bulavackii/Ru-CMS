{{-- Новый admin.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Панель управления')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 flex">

    @include('layouts.admin.sidebar')

    <div class="flex-1 flex flex-col">
        @include('layouts.admin.navbar')
        @include('layouts.admin.header')

        <main class="p-6 flex-1">
            @yield('content')
        </main>

        @include('layouts.admin.footer')
    </div>

    {{-- Подключение JS для слайдшоу --}}
<script src="{{ asset('admin-assets/js/slideshow.js') }}"></script>

</body>
</html>
