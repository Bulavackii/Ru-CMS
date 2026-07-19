@props(['user' => auth()->user()])

<header class="relative text-sm text-gray-800 leading-tight z-10">
    {{-- фон-паттерн берём из var(--bg-image) --}}
    <div class="absolute inset-0 z-[-10] opacity-10"
         style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

    <div class="relative z-[999] backdrop-blur-md shadow border-b border-gray-200 dark:border-gray-700"
         style="background:var(--color-header,#ffffff); color:var(--color-text,#111827)">

        {{-- Верхняя панель --}}
        <div class="max-w-screen-xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            {{-- Лого --}}
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2 text-2xl font-extrabold hover:opacity-90 transition"
                   style="color:var(--color-primary,#2563eb)">
                    <div class="text-white font-bold w-8 h-8 flex items-center justify-center shadow-inner text-sm tracking-wide rounded"
                         style="background:var(--color-primary,#2563eb)">{{ __('RU') }}</div>
                    <span class="hidden sm:inline">CMS</span>
                </a>
                <span class="text-xs opacity-70 hidden sm:inline">Контент & Управление</span>
            </div>

            @php
                use Modules\News\Models\News;
                $cart = session('cart', []);
                $cartCount = array_sum(array_column($cart, 'qty'));
                $hasProducts = News::where('template', 'products')->exists();
            @endphp

            <div class="flex flex-wrap justify-center sm:justify-end items-center gap-3 text-sm">
                @if ($hasProducts)
                    <a href="{{ route('cart.index') }}" class="relative hover:opacity-90 transition" title="Корзина"
                       style="color:var(--color-text,#111827)">
                        @themeIcon('shopping-cart','text-lg')
                        <span id="cart-count"
                              class="absolute -top-2 -right-2 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center {{ $cartCount == 0 ? 'hidden' : '' }}"
                              style="background:var(--color-primary,#2563eb)">
                            {{ $cartCount }}
                        </span>
                    </a>
                @endif

                @auth
                    <a href="{{ route('dashboard') }}" class="hover:opacity-90 transition" title="Личный кабинет"
                       style="color:var(--color-text,#111827)">@themeIcon('user') Кабинет</a>
                    @if ($user->is_admin ?? false)
                        <a href="{{ url('/admin/modules') }}" class="hover:opacity-90 transition" title="Панель администратора"
                           style="color:var(--color-text,#111827)">@themeIcon('cogs') Админка</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="transition" style="color:#ef4444">@themeIcon('sign-out-alt') Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:opacity-90 transition" style="color:var(--color-text,#111827)">
                        @themeIcon('sign-in-alt') Войти</a>
                    <a href="{{ route('register') }}" class="hover:opacity-90 transition" style="color:var(--color-text,#111827)">
                        @themeIcon('user-plus') Регистрация</a>
                @endauth
            </div>
        </div>

        <div class="border-t border-gray-200" style="background:var(--color-header,#ffffff); color:var(--color-text,#111827)">
            <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-4">
                <nav class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm font-medium">
                    @foreach ([['/', 'home', 'Главная'], ['/about', 'book', 'О нас'], ['/faq', 'question-circle', 'Вопросы'], ['/contacts', 'phone-alt', 'Контакты']] as [$url, $icon, $title])
                        <a href="{{ $url }}" class="hover:opacity-90 {{ request()->is(ltrim($url, '/')) ? 'font-semibold' : '' }}"
                           style="color:var(--color-text,#111827)">
                            @themeIcon($icon) {{ $title }}
                        </a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('frontend.search') }}" class="flex items-center gap-2 w-full md:w-auto">
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="px-3 py-1.5 border rounded-md shadow-sm text-sm w-full md:w-64 focus:outline-none"
                           style="border-color:#d1d5db; background:#fff; color:var(--color-text,#111827)" placeholder="Поиск...">
                    <button type="submit" class="text-xl" title="Поиск" style="color:var(--color-primary,#2563eb)">
                        @themeIcon('search')
                    </button>
                </form>
            </div>
        </div>

        @php
            $menu = Modules\Menu\Models\Menu::with([
                'items' => fn($q) => $q->whereNull('parent_id')->orderBy('order')->with('children.children'),
            ])->where('position','header')->where('active',true)->first();
        @endphp

        @if ($menu && $menu->items->count())
            <div class="border-t border-gray-200 z-[999] relative"
                 style="background:var(--color-header,#ffffff); color:var(--color-text,#111827)">
                <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-wrap gap-4">
                    <nav class="relative text-sm font-medium flex flex-wrap gap-4">
                        @foreach ($menu->items as $item)
                            {!! renderMenuItem($item) !!}
                        @endforeach
                    </nav>
                </div>
            </div>
        @endif
    </div>
</header>
