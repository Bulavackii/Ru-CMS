@props([
    'user' => auth()->user(),
])

<header class="bg-white shadow-md border-b">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">

        {{-- Логотип / бренд --}}
        <div class="flex items-center space-x-3">
            <a href="/" class="text-2xl font-extrabold text-blue-600 hover:underline">
                🛍️ RuShop CMS
            </a>
            <span class="text-xs text-gray-400 hidden sm:inline">— Контент & Управление</span>
        </div>

        {{-- Основная навигация --}}
        <nav class="hidden md:flex items-center space-x-6 text-sm">
            <a href="/" class="hover:text-blue-600 {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">🏠 Главная</a>
            <a href="/about" class="hover:text-blue-600 {{ request()->is('about') ? 'text-blue-600 font-semibold' : '' }}">📘 О нас</a>
            <a href="/faq" class="hover:text-blue-600 {{ request()->is('faq') ? 'text-blue-600 font-semibold' : '' }}">❓ Вопросы</a>
            <a href="/contacts" class="hover:text-blue-600 {{ request()->is('contacts') ? 'text-blue-600 font-semibold' : '' }}">📞 Контакты</a>
        </nav>

        {{-- Панель управления и действия --}}
        <div class="flex items-center space-x-4 text-sm">
            @auth
                <a href="/dashboard" class="hover:text-blue-600">👤 Кабинет</a>
                @if ($user->is_admin ?? false)
                    <a href="/admin/modules" class="hover:text-blue-600">⚙️ Админка</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 hover:underline">Выйти</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-blue-600">🔑 Войти</a>
                <a href="{{ route('register') }}" class="hover:text-blue-600">📝 Регистрация</a>
            @endauth

            {{-- Язык --}}
            {{-- <form method="POST" action="{{ route('locale.change') }}">
                @csrf
                <select name="locale" onchange="this.form.submit()"
                        class="text-sm border-none bg-transparent focus:outline-none cursor-pointer">
                    <option value="ru" {{ app()->getLocale() === 'ru' ? 'selected' : '' }}>🇷🇺</option>
                    <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>🇬🇧</option>
                </select>
            </form> --}}
        </div>
    </div>
</header>
