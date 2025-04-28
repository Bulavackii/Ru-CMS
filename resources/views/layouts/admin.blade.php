<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Панель управления')</title>

    {{-- Подключение CSS и JS через Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
    <nav class="bg-gray-800 text-white p-4">
        <div class="container mx-auto">
            <a href="/admin/modules" class="font-bold">RuShop CMS Admin</a>
        </div>
    </nav>

    <main class="py-6">
        <div class="container mx-auto">
            @yield('content')
        </div>
    </main>
</body>
</html>
