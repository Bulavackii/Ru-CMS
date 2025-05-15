@props([
    'user' => auth()->user(),
])

<header class="relative text-sm text-gray-800 leading-tight">
    {{-- 🖼️ Фоновое изображение --}}
    <div class="absolute inset-0 z-0 opacity-10"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 🌫️ Контейнер контента --}}
    <div class="relative z-10 bg-white/80 backdrop-blur-md shadow border-b border-gray-200">

        {{-- 🔷 Верхний ярус --}}
        <div class="max-w-screen-xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">

            {{-- Лого --}}
            <div class="flex items-center gap-3">
                <a href="/" class="text-2xl font-extrabold text-blue-600 hover:text-blue-700 transition">
                    🛍️ <span class="hidden sm:inline">RuShop CMS</span>
                </a>
                <span class="text-xs text-gray-500 hidden sm:inline">Контент & Управление</span>
            </div>

            {{-- 👤 Аккаунт + корзина --}}
            @php
                use Modules\News\Models\News;

                $cart = session('cart', []);
                $cartCount = array_sum(array_column($cart, 'qty'));
                $hasProducts = News::where('template', 'products')->exists();
            @endphp

            <div class="flex flex-wrap justify-center sm:justify-end items-center gap-3 text-sm text-gray-700">

                {{-- 🛒 Корзина --}}
                @if ($hasProducts)
                    <a href="{{ route('cart.index') }}" class="relative hover:text-blue-600 transition" id="cart-button">
                        🛒
                        @if ($cartCount > 0)
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>
                @endif

                {{-- 👤 Аккаунт --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">👤 Кабинет</a>

                    @if ($user->is_admin ?? false)
                        <a href="{{ url('/admin/modules') }}" class="hover:text-blue-600 transition">⚙️ Админка</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-700 transition">🚪 Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-blue-600 transition">🔑 Войти</a>
                    <a href="{{ route('register') }}" class="hover:text-blue-600 transition">📝 Регистрация</a>
                @endauth
            </div>
        </div>

        {{-- 📌 Нижний ярус: навигация + поиск --}}
        <div class="border-t border-gray-200 bg-white/90 dark:bg-gray-800/90">
            <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-4">

                {{-- 📌 Навигация --}}
                <nav class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <a href="{{ url('/') }}" class="hover:text-blue-600 transition {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">
                        🏠 Главная
                    </a>
                    <a href="{{ url('/about') }}" class="hover:text-blue-600 transition {{ request()->is('about') ? 'text-blue-600 font-semibold' : '' }}">
                        📘 О нас
                    </a>
                    <a href="{{ url('/faq') }}" class="hover:text-blue-600 transition {{ request()->is('faq') ? 'text-blue-600 font-semibold' : '' }}">
                        ❓ Вопросы
                    </a>
                    <a href="{{ url('/contacts') }}" class="hover:text-blue-600 transition {{ request()->is('contacts') ? 'text-blue-600 font-semibold' : '' }}">
                        📞 Контакты
                    </a>
                </nav>

                {{-- 🔍 Поиск --}}
                <form method="GET" action="{{ route('frontend.search') }}"
                      class="flex items-center gap-2 w-full md:w-auto">
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="🔎 Поиск...">
                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xl">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>
