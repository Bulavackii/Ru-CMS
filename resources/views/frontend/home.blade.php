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

<main class="flex-grow py-10">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-6 text-center">Новости</h2>

        {{-- Фильтр по категориям --}}
        <form method="GET" class="mb-6 flex flex-wrap gap-4 justify-center items-center">
            <select name="category" class="border px-3 py-2 rounded">
                <option value="">Все категории</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->title }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Фильтровать
            </button>
        </form>

        {{-- Список новостей --}}
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($newsList as $news)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-4 flex flex-col">
                    <h3 class="text-lg font-semibold mb-2">{{ $news->title }}</h3>
                    <p class="text-gray-600 text-sm mb-2">
                        Категории:
                        @forelse ($news->categories as $category)
                            <a href="{{ url('/?category=' . $category->id) }}"
                               class="text-blue-600 hover:underline">
                                {{ $category->title }}
                            </a>@if (!$loop->last), @endif
                        @empty
                            <span class="text-gray-400">Без категории</span>
                        @endforelse
                    </p>
                    <div class="text-sm text-gray-700 mb-4">
                        {!! Str::limit(strip_tags($news->content), 100) !!}
                    </div>
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto text-blue-600 hover:underline text-sm font-medium">
                        Читать далее →
                    </a>
                </div>
            @empty
                <p class="text-center text-gray-500 col-span-full">Новостей пока нет.</p>
            @endforelse
        </div>

        {{-- Пагинация --}}
        <div class="mt-8">
            {{ $newsList->withQueryString()->links() }}
        </div>
    </div>
</main>

<footer class="bg-white text-center text-sm text-gray-500 py-4 border-t mt-10">
    &copy; {{ date('Y') }} RuShop CMS. Все права защищены.
</footer>

</body>
</html>
