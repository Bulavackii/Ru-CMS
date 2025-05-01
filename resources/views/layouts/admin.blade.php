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
            <a href="/admin/search" class="block px-4 py-2 hover:bg-gray-100">Поиск</a>
        </nav>
    </aside>

    {{-- Контент --}}
    <div class="flex-1 flex flex-col">

        {{-- Верхняя тёмная навигация --}}
        <nav class="bg-gray-800 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex gap-4">
                    <a href="/" class="font-bold hover:underline">🏠 На сайт</a>
                    <a href="{{ route('admin.modules.index') }}" class="hover:underline">⚙️ Модули</a>
                    <a href="{{ route('admin.search.index') }}" class="hover:underline">🔍 Поиск</a>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hover:underline">🚪 Выйти</button>
                </form>
            </div>
        </nav>

        {{-- Шапка с поиском --}}
        <header class="bg-white p-4 shadow text-xl font-semibold flex justify-between items-center">
            <div>
                @yield('header', 'Панель администратора')
            </div>

            {{-- Глобальный поиск --}}
            <form method="GET" action="{{ route('admin.search.index') }}" class="flex space-x-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск..."
                       class="border rounded px-3 py-1 text-sm w-64">
                <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">🔍</button>
            </form>
        </header>

        <main class="p-6 flex-1">
            @yield('content')
        </main>
    </div>

</body>
</html>
