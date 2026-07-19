@props(['user' => auth()->user()])

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // ==== Параметры темы для логотипа ====
  $theme = $activeTheme ?? null;
  $cfg   = $theme->config ?? [];

  $rawLogo   = (string) data_get($cfg, 'logo_url', '');
  $logoPos   = (string) data_get($cfg, 'logo_position', 'left');
  $wRaw      = data_get($cfg, 'logo_width', '120px');
  $logoW     = is_numeric($wRaw) ? ($wRaw.'px') : (trim((string)$wRaw) ?: '120px');

  // Абсолютный URL логотипа (учитываем разные варианты хранения пути)
  $logoAbs = null;
  if ($rawLogo !== '') {
      $isHttp = (bool) preg_match('~^https?://~i', $rawLogo);

      if ($isHttp) {
          $logoAbs = $rawLogo; // уже абсолютный URL
      } else {
          // нормализуем относительный путь
          $raw = ltrim($rawLogo, '/');

          // если путь начинается с storage/, проверяем в диске public
          if (Str::startsWith($raw, 'storage/')) {
              $rel = ltrim(Str::after($raw, 'storage/'), '/');
              if (Storage::disk('public')->exists($rel)) {
                  $logoAbs = asset('storage/'.$rel);
              }
          }

          // если ещё не нашли — вдруг файл лежит прямо в public/
          if (!$logoAbs && file_exists(public_path($raw))) {
              $logoAbs = asset($raw);
          }

          // последний шанс: возможно, указали без ведущего storage/
          if (!$logoAbs && Storage::disk('public')->exists($raw)) {
              $logoAbs = asset('storage/'.$raw);
          }
      }
  }

  // позиционирование логотипа
  $logoWrapCls = $logoPos === 'center'
      ? 'sm:mx-auto'
      : ($logoPos === 'right' ? 'sm:ml-auto' : '');

  // цвета
  $headerBg = 'var(--color-header,#ffffff)';
  $textCol  = 'var(--color-text,#111827)';
@endphp

<header class="relative text-sm text-gray-800 leading-tight z-10">
  {{-- фон-паттерн берём из темы --}}
  <div class="absolute inset-0 z-[-10] opacity-10"
       style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

    <div class="relative z-[999] backdrop-blur-md shadow border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-colors duration-200"
       style="color:{{ $textCol }}">

    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 sm:py-4 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
      {{-- ========= ЛОГОТИП (из темы) ========= --}}
      <div class="flex items-center gap-3 {{ $logoWrapCls }}">
        <a href="{{ url('/') }}"
           class="flex items-center gap-2 text-2xl font-extrabold hover:opacity-90 transition"
           aria-label="На главную"
           style="color:var(--color-primary,#2563eb)">

          @if($logoAbs)
            <img
              src="{{ $logoAbs }}"
              alt="Логотип"
              loading="lazy"
              decoding="async"
              style="width: {{ $logoW }}; max-width: 100%; height: auto;"
              class="inline-block align-middle"
              onerror="this.style.display='none'">
          @else
            <div class="text-white font-bold w-8 h-8 flex items-center justify-center shadow-inner text-sm tracking-wide rounded"
                 style="background:var(--color-primary,#2563eb)">RU</div>
            <span class="hidden sm:inline">CMS</span>
          @endif
        </a>

        <span class="text-xs opacity-70 hidden sm:inline">Контент & Управление</span>
      </div>
      {{-- ======== /ЛОГОТИП ======== --}}

      @php
        // Корзина, если используется шаблон products
        $cart       = session('cart', []);
        $cartCount  = array_sum(array_column($cart, 'qty'));
        $hasProducts = \Modules\News\Models\News::where('template', 'products')->exists();
      @endphp

      {{-- ========= ПРАВЫЕ ССЫЛКИ ========= --}}
      <div class="hdr-actions">
        {{-- Переключатель темы --}}
        <button x-data="{
            darkMode: false,
            init() {
                // Светлая тема по умолчанию — включаем тёмную только по явному выбору пользователя
                this.darkMode = localStorage.getItem('darkMode') === 'true';
                this.applyTheme();
            },
            toggle() {
                this.darkMode = !this.darkMode;
                this.applyTheme();
                localStorage.setItem('darkMode', this.darkMode);
            },
            applyTheme() {
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        }" @click="toggle()" class="hdr-link" title="Переключить тему" style="color:var(--color-text,#111827)">
          <i class="fas" :class="darkMode ? 'fa-sun text-yellow-500' : 'fa-moon'"></i>
          <span class="hidden sm:inline" x-text="darkMode ? 'Светлая' : 'Тёмная'"></span>
        </button>
        
        @if ($hasProducts)
          <a href="{{ route('cart.index') }}" class="hdr-link" title="Корзина" style="color:var(--color-text,#111827)">
            <span class="cart-ico">
              <span class="cart-ico__glyph">@themeIcon('shopping-cart')</span>
              <span id="cart-count" class="cart-ico__badge {{ $cartCount == 0 ? 'hidden' : '' }}">
                {{ $cartCount }}
              </span>
            </span>
            <span>Корзина</span>
          </a>
        @endif

        @auth
          <a href="{{ route('dashboard') }}" class="hdr-link" title="Личный кабинет" style="color:var(--color-text,#111827)">
            @themeIcon('user') <span>Кабинет</span>
          </a>

          @if (($user->is_admin ?? false))
            <a href="{{ url('/admin/modules') }}" class="hdr-link" title="Панель администратора" style="color:var(--color-text,#111827)">
              @themeIcon('mail') <span>Админка</span>
            </a>
          @endif

          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="hdr-link text-red-500" title="Выйти">
              @themeIcon('login') <span>Выйти</span>
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="hdr-link" style="color:var(--color-text,#111827)">
            @themeIcon('user') <span>Войти</span>
          </a>
          <a href="{{ route('register') }}" class="hdr-link" style="color:var(--color-text,#111827)">
            @themeIcon('user') <span>Регистрация</span>
          </a>
        @endauth
      </div>
      {{-- ======== /ПРАВЫЕ ССЫЛКИ ======== --}}
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-colors duration-200" style="color:{{ $textCol }};">
      <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 sm:py-4 flex flex-col md:flex-row items-center justify-between gap-3 sm:gap-4">
        <nav class="header-nav flex flex-wrap justify-center md:justify-start items-center gap-1 sm:gap-2 text-sm font-medium">
          @foreach ([['/', 'home', 'Главная'], ['/about', 'eye', 'О нас'], ['/faq', 'book', 'Вопросы'], ['/contacts', 'user', 'Контакты']] as [$url, $icon, $title])
            <a href="{{ $url }}"
               class="px-2 py-1 rounded-md transition
                      {{ request()->is(ltrim($url, '/')) ? 'bg-gray-100 dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700 active-link' : '' }}"
               style="color:var(--color-text,#111827)"
               {{ request()->is(ltrim($url, '/')) ? 'aria-current=page' : '' }}
               title="{{ $title }}">
              @themeIcon($icon) {{ $title }}
            </a>
          @endforeach
        </nav>

        <form method="GET" action="{{ route('frontend.search') }}" class="flex items-center gap-2 w-full md:w-auto">
          <input type="text" name="q" value="{{ request('q') }}"
                 class="px-3 py-1.5 border rounded shadow-sm text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors
                        border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-700 
                        text-gray-900 dark:text-gray-100 
                        placeholder-gray-500 dark:placeholder-gray-400"
                 placeholder="Поиск...">
          <button type="submit" class="text-xl text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" title="Поиск">
            @themeIcon('search')
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- Стили --}}
  <style>
    .header-nav a:hover{
      color: var(--color-primary,#2563eb);
      background: rgba(37,99,235,.08);
    }
    .dark .header-nav a:hover{ background: rgba(255,255,255,.06); }
    .header-nav a.active-link{ color: var(--color-primary,#2563eb); }

    /* === Единая высота и выравнивание правых ссылок === */
    .hdr-actions{
      display:flex; flex-wrap:wrap; align-items:center; justify-content:center;
      gap:.75rem;
    }
    .hdr-link{
      display:inline-flex; align-items:center; gap:.4rem;
      height:30px; line-height:30px;
      padding:0 .1rem; white-space:nowrap;
      transition:opacity .15s ease;
    }
    .hdr-link:hover{ opacity:.9; }

    .hdr-link > i, .hdr-link > svg{
      width:1.4rem; height:1.4rem; font-size:1.25rem; line-height:1; display:inline-block;
    }
    @media (min-width:768px){
      .hdr-link{ height:32px; line-height:32px; }
      .hdr-link > i, .hdr-link > svg{ width:1.5rem; height:1.5rem; font-size:1.35rem; }
    }

    /* === Корзина === */
    .cart-ico{ position: relative; display:inline-flex; align-items:center; }
    .cart-ico__glyph{ display:inline-flex; line-height:1; font-size:1.35rem; }
    @media (min-width:768px){ .cart-ico__glyph{ font-size:1.5rem; } }
    .cart-ico__badge{
      position:absolute; top:-7px; left:-7px;
      min-width:1.15rem; height:1.15rem; padding:0 .18rem;
      display:flex; align-items:center; justify-content:center;
      font-size:.72rem; line-height:1; color:#fff;
      background:var(--color-primary,#2563eb);
      border-radius:999px; box-shadow:0 0 0 2px #fff;
    }
    @media (max-width:767px){
      .cart-ico__badge{ top:-6px; left:-6px; min-width:1.05rem; height:1.05rem; font-size:.70rem; }
    }

    /* ========= FORCE LIGHT только для хэдера ========= */
    @media (prefers-color-scheme: dark){
      .dark\:border-gray-700{ border-color:#e5e7eb !important; }
      .dark\:bg-gray-800{ background-color:#f3f4f6 !important; }
    }
  </style>

  {{-- JS: фолбэк для «пустых» иконок темы --}}
  <script>
    (function(){
      var root = document.currentScript.closest('header');
      if(!root) return;

      var DOT_SIZE = 8;
      var fallbackSVG =
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 8" width="'+DOT_SIZE+'" height="'+DOT_SIZE+'" '+
        'style="display:inline-block;vertical-align:middle;margin-right:6px" aria-hidden="true">'+
        '<circle cx="4" cy="4" r="4" fill="#111827"/></svg>';

      function tryReplace(el){
        try{
          var box = el.getBoundingClientRect();
          if (box.width < 6) {
            var span = document.createElement('span');
            span.innerHTML = fallbackSVG;
            el.replaceWith(span.firstElementChild);
          }
        }catch(e){}
      }

      root.querySelectorAll('i, svg').forEach(tryReplace);
      setTimeout(function(){ root.querySelectorAll('i, svg').forEach(tryReplace); }, 600);
    })();
  </script>
</header>
