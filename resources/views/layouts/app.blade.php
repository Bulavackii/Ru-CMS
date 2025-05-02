<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css'])
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-800">

    <div class="min-h-screen flex flex-col">
        {{-- Навигация --}}
        @include('layouts.navigation')

        {{-- Хедер --}}
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div>{{ $header }}</div>

                    {{-- Поиск --}}
                    <form method="GET" action="{{ route('admin.search.index') }}" class="flex space-x-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск..."
                            class="border px-3 py-1 rounded text-sm w-64">
                        <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">🔍</button>
                    </form>
                </div>
            </header>
        @endisset

        {{-- Основной контент --}}
        <main class="flex-grow py-10">
            <div class="container mx-auto px-4">
                {{ $slot }}
            </div>
        </main>

        {{-- Футер --}}
        <footer class="bg-white text-center text-sm text-gray-500 py-4 border-t">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </footer>
    </div>
</body>

</html>
