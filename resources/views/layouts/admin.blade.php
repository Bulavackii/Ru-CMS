<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Панель управления')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 flex">

    {{-- Сайдбар --}}
    <aside class="w-64 bg-white h-screen shadow-md">
        <div class="p-6 font-bold text-lg border-b">RuShop Admin</div>
        <nav class="mt-4 space-y-2">
            <a href="/admin/modules" class="block px-4 py-2 hover:bg-gray-100">Модули</a>
            <a href="/admin/users" class="block px-4 py-2 hover:bg-gray-100">Пользователи</a>
        </nav>
    </aside>

    {{-- Контент --}}
    <div class="flex-1 flex flex-col">
        <header class="bg-white p-4 shadow text-xl font-semibold">
            @yield('header', 'Админка')
        </header>

        <main class="p-6 flex-1">
            @yield('content')
        </main>
    </div>

</body>
</html>
