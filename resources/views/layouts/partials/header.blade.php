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
        <div class="max-w-screen-xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-4">

            {{-- 🔷 Логотип / Бренд --}}
            <div class="flex items-center gap-3">
                <a href="/" class="text-2xl font-extrabold text-blue-600 hover:text-blue-700 transition">
                    🛍️ <span class="hidden sm:inline">RuShop CMS</span>
                </a>
                <span class="text-xs text-gray-500 hidden sm:inline">Контент & Управление</span>
            </div>

            {{-- 📌 Меню навигации --}}
            <nav
                class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm font-medium text-gray-700">
                <a href="{{ url('/') }}"
                    class="transition hover:text-blue-600 {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">🏠
                    Главная</a>
                <a href="{{ url('/about') }}"
                    class="transition hover:text-blue-600 {{ request()->is('about') ? 'text-blue-600 font-semibold' : '' }}">📘
                    О нас</a>
                <a href="{{ url('/faq') }}"
                    class="transition hover:text-blue-600 {{ request()->is('faq') ? 'text-blue-600 font-semibold' : '' }}">❓
                    Вопросы</a>
                <a href="{{ url('/contacts') }}"
                    class="transition hover:text-blue-600 {{ request()->is('contacts') ? 'text-blue-600 font-semibold' : '' }}">📞
                    Контакты</a>
            </nav>

            {{-- 👤 Аккаунт пользователя + 🛒 Корзина --}}
            @php
                use Modules\News\Models\News;

                $cart = session('cart', []);
                $cartCount = array_sum(array_column($cart, 'qty'));

                // Проверка наличия хотя бы одного товара
                $hasProducts = News::where('template', 'products')->exists();
            @endphp

            <div class="flex items-center gap-4 text-sm text-gray-700 relative">
                {{-- 🛒 Корзина --}}
                @if ($hasProducts)
                    <a href="{{ route('cart.index') }}" class="relative hover:text-blue-600 transition" id="cart-button">
                        🛒
                        @if ($cartCount > 0)
                            <span
                                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
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
                        <button type="submit" class="text-red-600 hover:text-red-700 transition">
                            🚪 Выйти
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-blue-600 transition">🔑 Войти</a>
                    <a href="{{ route('register') }}" class="hover:text-blue-600 transition">📝 Регистрация</a>
                @endauth
            </div>
        </div>
    </div>
</header>
