<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добро пожаловать в RuShop CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <header class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-blue-600">RuShop CMS</h1>

            <nav class="space-x-4">
                @auth
                    <a href="/dashboard" class="text-sm text-gray-700 hover:text-blue-600">Личный кабинет</a>
                    @if ($user->is_admin)
                        <a href="/admin/modules" class="text-sm text-gray-700 hover:text-blue-600">Админка</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:underline">Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-blue-600">Войти</a>
                    <a href="{{ route('register') }}" class="text-sm text-gray-700 hover:text-blue-600">Регистрация</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center">
        <div class="text-center">
            <h2 class="text-3xl font-bold mb-4">Добро пожаловать в RuShop CMS</h2>
            <p class="text-gray-600 mb-6">Гибкая модульная система для магазинов и сайтов</p>

            @auth
                <a href="/dashboard" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Перейти в кабинет</a>
            @else
                <a href="{{ route('login') }}" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Войти</a>
            @endauth
        </div>
    </main>

    <footer class="bg-white text-center text-sm text-gray-500 py-4 border-t">
        &copy; {{ date('Y') }} RuShop CMS. Все права защищены.
    </footer>

</body>
</html>
