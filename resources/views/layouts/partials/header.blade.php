@props([
    'user' => auth()->user(),
])

<header class="bg-white shadow border-b border-gray-200">
    <div class="container mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-3">

        {{-- 🔷 Логотип / Название --}}
        <div class="flex items-center space-x-3">
            <a href="/" class="text-2xl font-extrabold text-blue-600 hover:text-blue-700 transition">
                🛍️ RuShop CMS
            </a>
            <span class="text-xs text-gray-400 hidden sm:inline">— Контент & Управление</span>
        </div>

        {{-- 📌 Навигация (адаптивная) --}}
        <nav class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm text-gray-700 font-medium">
            <a href="{{ url('/') }}" class="hover:text-blue-600 {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">🏠 Главная</a>
            <a href="{{ url('/about') }}" class="hover:text-blue-600 {{ request()->is('about') ? 'text-blue-600 font-semibold' : '' }}">📘 О нас</a>
            <a href="{{ url('/faq') }}" class="hover:text-blue-600 {{ request()->is('faq') ? 'text-blue-600 font-semibold' : '' }}">❓ Вопросы</a>
            <a href="{{ url('/contacts') }}" class="hover:text-blue-600 {{ request()->is('contacts') ? 'text-blue-600 font-semibold' : '' }}">📞 Контакты</a>
        </nav>

        {{-- 👤 Панель пользователя --}}
        <div class="flex items-center gap-4 text-sm text-gray-700">
            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">👤 Кабинет</a>

                @if ($user->is_admin ?? false)
                    <a href="{{ url('/admin/modules') }}" class="hover:text-blue-600">⚙️ Админка</a>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 hover:underline hover:text-red-700 transition">🚪 Выйти</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-blue-600">🔑 Войти</a>
                <a href="{{ route('register') }}" class="hover:text-blue-600">📝 Регистрация</a>
            @endauth
        </div>
    </div>
</header>
