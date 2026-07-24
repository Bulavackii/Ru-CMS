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
@endphp

{{--
    UI шапки переработан в общий стиль проекта (стекло + indigo-акцент .fx-*).
    ВАЖНО: логика (лого/тема/корзина/авторизация/поиск) и подключение навигации
    из модуля Меню (@include('Menu::frontend.header')) НЕ менялись — только
    компоновка и оформление самой шапки.
--}}
<header class="fx-header relative text-sm z-10">
  {{-- фон-паттерн берём из темы --}}
  <div class="absolute inset-0 z-[-10] opacity-10"
       style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

  <div class="hdr-glass relative z-[999] border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">

    {{-- ═══════════ Ряд 1: логотип + действия ═══════════ --}}
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">

      {{-- ЛОГОТИП --}}
      <a href="{{ url('/') }}" class="hdr-logo flex items-center gap-2.5 {{ $logoWrapCls }}" aria-label="На главную">
        @if($logoAbs)
          <img src="{{ $logoAbs }}" alt="Логотип" loading="lazy" decoding="async"
               style="width: {{ $logoW }}; max-width:100%; height:auto;" class="inline-block align-middle"
               onerror="this.style.display='none'">
        @else
          <span class="hdr-logo-badge">RU</span>
          <span class="leading-tight">
            <span class="hdr-logo-name block">CMS</span>
            <span class="hdr-logo-sub hidden sm:block">Контент &amp; Управление</span>
          </span>
        @endif
      </a>

      @php
        // Корзина, если используется шаблон products
        $cart       = session('cart', []);
        $cartCount  = array_sum(array_column($cart, 'qty'));
        $hasProducts = \Modules\News\Models\News::where('template', 'products')->exists();
      @endphp

      {{-- ДЕЙСТВИЯ --}}
      <div class="hdr-actions flex items-center flex-wrap justify-center gap-1.5">

        {{-- Переключатель темы (логика Alpine не менялась) --}}
        <button x-data="{
            darkMode: false,
            init() {
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
        }" @click="toggle()" type="button" class="hdr-icon-btn" title="Переключить тему">
          <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
          <span class="hidden lg:inline" x-text="darkMode ? 'Светлая' : 'Тёмная'"></span>
        </button>

        @if ($hasProducts)
          <a href="{{ route('cart.index') }}" class="hdr-pill" title="Корзина">
            <span class="cart-ico">
              <span class="cart-ico__glyph"><i class="fas fa-cart-shopping"></i></span>
              <span id="cart-count" class="cart-ico__badge {{ $cartCount == 0 ? 'hidden' : '' }}">{{ $cartCount }}</span>
            </span>
            <span class="hidden lg:inline">Корзина</span>
          </a>
        @endif

        @auth
          <a href="{{ route('dashboard') }}" class="hdr-pill" title="Личный кабинет">
            <i class="fas fa-user"></i><span class="hidden md:inline">Кабинет</span>
          </a>

          @if (($user->is_admin ?? false))
            <a href="{{ url('/admin/modules') }}" class="hdr-pill hdr-pill--accent" title="Панель администратора">
              <i class="fas fa-gauge-high"></i><span class="hidden md:inline">Админка</span>
            </a>
          @endif

          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="hdr-pill hdr-pill--danger" title="Выйти">
              <i class="fas fa-right-from-bracket"></i><span class="hidden md:inline">Выйти</span>
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="hdr-pill" title="Войти">
            <i class="fas fa-right-to-bracket"></i><span class="hidden md:inline">Войти</span>
          </a>
          <a href="{{ route('register') }}" class="hdr-pill hdr-pill--accent" title="Регистрация">
            <i class="fas fa-user-plus"></i><span class="hidden md:inline">Регистрация</span>
          </a>
        @endauth
      </div>
    </div>

    {{-- ═══════════ Ряд 2: навигация (модуль Меню) + поиск ═══════════ --}}
    <div class="hdr-row2 border-t border-gray-200 dark:border-gray-700">
      <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-2.5 sm:py-3 flex flex-col md:flex-row items-center justify-between gap-3 sm:gap-4">

        {{-- НАВИГАЦИЯ: меню из модуля Меню (позиция header). НЕ ТРОГАЕМ. --}}
        @include('Menu::frontend.header')

        {{-- Поиск --}}
        <form method="GET" action="{{ route('frontend.search') }}" class="w-full md:w-auto md:ml-auto">
          <div class="hdr-search relative w-full md:w-72">
            <button type="submit" class="hdr-search-btn" title="Поиск" aria-label="Искать">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
              </svg>
            </button>
            <input type="text" name="q" value="{{ request('q') }}" class="hdr-search-input" placeholder="Поиск…">
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== Оформление шапки (стиль проекта .fx-*) ===== --}}
  <style>
    .hdr-glass{ background:rgba(255,255,255,.82); -webkit-backdrop-filter:blur(14px); backdrop-filter:blur(14px); }
    :root.dark .hdr-glass{ background:rgba(15,23,42,.8); }

    /* Логотип */
    .hdr-logo{ text-decoration:none; }
    .hdr-logo-badge{ width:2rem; height:2rem; border-radius:.6rem; display:inline-flex; align-items:center;
        justify-content:center; background:var(--fx-grad,#6366f1); color:#fff; font-weight:700; font-size:.8rem;
        letter-spacing:.02em; flex:0 0 auto; box-shadow:0 8px 18px -8px rgba(99,102,241,.6); }
    .hdr-logo-name{ font-weight:800; font-size:1.3rem; line-height:1.05; color:#6366f1;
        background:var(--fx-grad,#6366f1); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
    .hdr-logo-sub{ font-size:.66rem; color:#6b7280; font-weight:500; letter-spacing:.02em; margin-top:1px; }
    :root.dark .hdr-logo-sub{ color:#9ca3af; }

    /* Пилюли-действия */
    .hdr-actions .hdr-pill, .hdr-actions .hdr-icon-btn{
        display:inline-flex; align-items:center; gap:.4rem; padding:.42rem .72rem; border-radius:9px;
        font-size:.82rem; font-weight:500; line-height:1; color:#374151; background:transparent; border:0;
        cursor:pointer; text-decoration:none; white-space:nowrap;
        transition:background .14s ease, color .14s ease, filter .14s ease; }
    :root.dark .hdr-actions .hdr-pill, :root.dark .hdr-actions .hdr-icon-btn{ color:#d1d5db; }
    .hdr-actions .hdr-icon-btn{ padding:.42rem .58rem; }
    .hdr-actions .hdr-pill i, .hdr-actions .hdr-icon-btn i{ font-size:1rem; line-height:1; }
    .hdr-actions .hdr-pill:hover, .hdr-actions .hdr-icon-btn:hover{ background:rgba(99,102,241,.1); color:#4f46e5; }
    :root.dark .hdr-actions .hdr-pill:hover, :root.dark .hdr-actions .hdr-icon-btn:hover{ background:rgba(99,102,241,.2); color:#c7d2fe; }
    .hdr-actions .hdr-pill--accent{ background:var(--fx-grad,#6366f1); color:#fff; box-shadow:0 8px 18px -10px rgba(99,102,241,.7); }
    .hdr-actions .hdr-pill--accent:hover{ background:var(--fx-grad,#6366f1); color:#fff; filter:brightness(1.08); }
    .hdr-actions .hdr-pill--danger{ color:#e11d48; }
    .hdr-actions .hdr-pill--danger:hover{ background:rgba(244,63,94,.12); color:#e11d48; }

    /* Поиск */
    .hdr-search-input{ width:100%; padding:.55rem .9rem .55rem 2.3rem; border-radius:10px; font-size:.85rem; line-height:1.2;
        border:1px solid rgba(17,24,39,.14); background:rgba(255,255,255,.7); color:#111827;
        transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    :root.dark .hdr-search-input{ background:rgba(30,41,59,.7); border-color:rgba(255,255,255,.12); color:#f3f4f6; }
    .hdr-search-input::placeholder{ color:#9ca3af; }
    .hdr-search-input:focus{ outline:none; border-color:#818cf8; box-shadow:0 0 0 3px rgba(99,102,241,.18); background:#fff; }
    :root.dark .hdr-search-input:focus{ background:rgba(30,41,59,.96); }
    .hdr-search-btn{ position:absolute; left:.6rem; top:50%; transform:translateY(-50%); z-index:2; display:inline-flex;
        padding:0; border:0; background:transparent; color:#9ca3af; cursor:pointer; transition:color .15s ease; }
    .hdr-search-btn:hover{ color:#6366f1; }

    /* Корзина */
    .cart-ico{ position:relative; display:inline-flex; align-items:center; }
    .cart-ico__glyph{ display:inline-flex; line-height:1; font-size:1rem; }
    .cart-ico__badge{ position:absolute; top:-7px; left:-7px; min-width:1.05rem; height:1.05rem; padding:0 .18rem;
        display:flex; align-items:center; justify-content:center; font-size:.68rem; line-height:1; color:#fff;
        background:var(--fx-a,#6366f1); border-radius:999px; box-shadow:0 0 0 2px #fff; }
    :root.dark .cart-ico__badge{ box-shadow:0 0 0 2px #0f172a; }
  </style>
</header>
