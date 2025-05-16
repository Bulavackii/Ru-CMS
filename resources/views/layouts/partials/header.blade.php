@props(['user' => auth()->user()])

<header class="relative text-sm text-gray-800 leading-tight z-10">
    {{-- 🗾️ Фон --}}
    <div class="absolute inset-0 z-[-10] opacity-10"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;"></div>

    <div class="relative z-[999] bg-white/80 backdrop-blur-md shadow border-b border-gray-200">
        {{-- 🔷️ Верх --}}
        <div class="max-w-screen-xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/" class="text-2xl font-extrabold text-blue-600 hover:text-blue-700 transition">🛍️ <span class="hidden sm:inline">RuShop CMS</span></a>
                <span class="text-xs text-gray-500 hidden sm:inline">Контент & Управление</span>
            </div>

            @php
                use Modules\News\Models\News;
                $cart = session('cart', []);
                $cartCount = array_sum(array_column($cart, 'qty'));
                $hasProducts = News::where('template', 'products')->exists();
            @endphp

            <div class="flex flex-wrap justify-center sm:justify-end items-center gap-3 text-sm text-gray-700">
                @if ($hasProducts)
                    <a href="{{ route('cart.index') }}" class="relative hover:text-blue-600 transition">
                        🛒
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center {{ $cartCount == 0 ? 'hidden' : '' }}">
                            {{ $cartCount }}
                        </span>
                    </a>
                @endif
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

        {{-- 📜 Навигация + Поиск --}}
        <div class="border-t border-gray-200 bg-white/90 dark:bg-gray-800/90">
            <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-4">
                <nav class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <a href="/" class="hover:text-blue-600 {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">🏠 Главная</a>
                    <span class="hidden sm:inline text-gray-300">│</span>
                    <a href="/about" class="hover:text-blue-600 {{ request()->is('about') ? 'text-blue-600 font-semibold' : '' }}">📘 О нас</a>
                    <span class="hidden sm:inline text-gray-300">│</span>
                    <a href="/faq" class="hover:text-blue-600 {{ request()->is('faq') ? 'text-blue-600 font-semibold' : '' }}">❓ Вопросы</a>
                    <span class="hidden sm:inline text-gray-300">│</span>
                    <a href="/contacts" class="hover:text-blue-600 {{ request()->is('contacts') ? 'text-blue-600 font-semibold' : '' }}">📞 Контакты</a>
                </nav>

                <form method="GET" action="{{ route('frontend.search') }}" class="flex items-center gap-2 w-full md:w-auto">
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="🔎 Поиск...">
                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xl">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- 📉 Меню --}}
        @php
            use Modules\Menu\Models\MenuItem;
            $menu = Modules\Menu\Models\Menu::with([
                'items' => fn($q) => $q->whereNull('parent_id')->orderBy('order')->with('children.children'),
            ])->where('position', 'header')->where('active', true)->first();

            $icons = ['url' => '🔗', 'page' => ' ☰ ', 'category' => '📌'];

            function renderMenuItem($item, $icons)
            {
                $link = match ($item->type) {
                    'url' => $item->url,
                    'page' => route('frontend.pages.show', ['slug' => optional($item->linkedPage)->slug]),
                    'category' => url('/?category=' . $item->linked_id),
                    default => '#',
                };
                $icon = $icons[$item->type] ?? '📌';
                $hasChildren = $item->children->isNotEmpty();
                $toggleId = 'submenu-' . $item->id;

                $html = '<div class="relative group inline-block">';
                $html .= '<div class="flex items-center gap-2">';

                if ($hasChildren) {
                    $html .= '<button type="button" class="toggle-btn text-gray-500 hover:text-blue-600 text-xs" data-target="' . $toggleId . '">▼</button>';
                }

                $html .= '<a href="{{ $link }}" class="text-sm min-w-[8rem] px-3 py-1 whitespace-nowrap text-left rounded hover:bg-blue-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">' . $icon . ' ' . $item->title . '</a>';
                $html .= '</div>';

                if ($hasChildren) {
                    $html .= '<div id="' . $toggleId . '" class="absolute left-0 top-full mt-2 min-w-[12rem] hidden bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded shadow z-[1000] p-2">';
                    foreach ($item->children as $child) {
                        $html .= renderMenuItem($child, $icons);
                    }
                    $html .= '</div>';
                }

                $html .= '</div>';
                return $html;
            }
        @endphp

        @if ($menu && $menu->items->count())
            <div class="border-t border-gray-200 bg-white/90 dark:bg-gray-800/90 z-[999] relative">
                <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-wrap gap-4">
                    <nav class="relative text-sm font-medium text-gray-700 dark:text-gray-300 flex flex-wrap gap-4">
                        @foreach($menu->items as $item)
                            {!! renderMenuItem($item, $icons) !!}
                        @endforeach
                    </nav>
                </div>
            </div>
        @endif
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.dataset.target;
                const submenu = document.getElementById(targetId);
                if (submenu) {
                    submenu.classList.toggle('hidden');
                    this.innerHTML = submenu.classList.contains('hidden') ? '▼' : '▲';
                }
            });
        });
    });
</script>
