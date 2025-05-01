<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Админка')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Стили -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- TinyMCE (если нужно) -->
    @stack('head')
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="container mx-auto px-4 py-8">
        @include('admin.partials.nav') {{-- если будет навигация --}}
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
