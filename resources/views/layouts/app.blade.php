<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RuShop CMS')</title>
    @viteReactRefresh
    {{-- Подключение стилей через Vite --}}
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    {{-- Навигация --}}
    <header class="bg-white shadow">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-lg font-semibold text-gray-900">
                RuShop CMS
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Выйти
                    </button>
                </form>
            @endauth
        </div>
    </header>

    {{-- Контент страницы --}}
    <main class="flex-grow">
        @yield('content')
    </main>

    {{-- Футер --}}
    <footer class="bg-white shadow mt-10">
        <div class="container mx-auto px-6 py-4 text-center text-sm text-gray-600">
            &copy; {{ date('Y') }} RuShop CMS. Все права защищены.
        </div>
    </footer>

</body>
</html>
