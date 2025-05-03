<header class="bg-white shadow-md p-4">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">
            <a href="/" class="hover:underline">RuShop CMS</a>
        </h1>

        <nav class="space-x-4">
            @auth
                <a href="/dashboard" class="text-sm text-gray-700 hover:text-blue-600">Личный кабинет</a>
                @if ($user->is_admin ?? false)
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
