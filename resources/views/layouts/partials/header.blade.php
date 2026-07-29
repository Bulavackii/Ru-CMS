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

  <div class="hdr-glass relative z-[999] transition-colors duration-200">

    {{-- ═══════════ Ряд 1: логотип + действия ═══════════ --}}
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">

      {{-- ЛОГОТИП --}}
      <a href="{{ url('/') }}" class="hdr-logo flex items-center gap-2.5 {{ $logoWrapCls }}" aria-label="{{ __('frontend.header.home_aria') }}">
        @if($logoAbs)
          <img src="{{ $logoAbs }}" alt="{{ __('frontend.header.logo_alt') }}" loading="lazy" decoding="async"
               style="width: {{ $logoW }}; max-width:100%; height:auto;" class="inline-block align-middle"
               onerror="this.style.display='none'">
        @else
          <span class="hdr-logo-badge">RU</span>
          <span class="leading-tight">
            <span class="hdr-logo-name block">CMS</span>
            <span class="hdr-logo-sub hidden sm:block">{{ __('frontend.header.tagline') }}</span>
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

        {{-- Переключатель языка: тянет ВСЕ доступные локали (resources/lang),
             переключает через frontend.locale.set → session app_locale. --}}
        @php
          $langNames = ['ru'=>'Русский','en'=>'English','be'=>'Беларуская','kk'=>'Қазақша','de'=>'Deutsch','fr'=>'Français','it'=>'Italiano'];
          $curLocale = app()->getLocale();
        @endphp
        <div x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false" class="hdr-lang relative">
          <button type="button" @click="open=!open" class="hdr-icon-btn" title="{{ __('frontend.header.language') }}" :aria-expanded="open.toString()">
            {!! locale_flag($curLocale) !!}
            <span class="hidden sm:inline">{{ strtoupper($curLocale) }}</span>
            <i class="fas fa-chevron-down" style="font-size:.58rem; opacity:.55"></i>
          </button>
          <div x-cloak x-show="open" x-transition class="hdr-lang-menu">
            @foreach(available_locales() as $code)
              <a href="{{ route('frontend.locale.set', $code) }}" class="hdr-lang-item {{ $code===$curLocale ? 'is-active' : '' }}">
                {!! locale_flag($code) !!}
                <span>{{ $langNames[$code] ?? strtoupper($code) }}</span>
                @if($code===$curLocale)<i class="fas fa-check" style="margin-left:auto; font-size:.7rem"></i>@endif
              </a>
            @endforeach
          </div>
        </div>

        {{-- Выбор темы оформления. Список берётся из модуля Темы: добавили тему
             в админке — она здесь, удалили — исчезла. Переключение — обычные
             ссылки, Alpine нужен только чтобы раскрыть список. --}}
        @php
            $themeList = $themeOptions ?? collect();
            $currentThemeSlug = $siteThemeSlug ?? null;
            $currentThemeTitle = $themeList->firstWhere('slug', $currentThemeSlug)->title
                ?? ($themeList->firstWhere('is_default', true)->title ?? 'Тема');
            $currentThemePrimary = $themeList->firstWhere('slug', $currentThemeSlug)->primary
                ?? ($themeList->firstWhere('is_default', true)->primary ?? '#6366f1');
        @endphp

        @if($themeList->isNotEmpty())
          <div x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false" class="hdr-lang relative">
            <button type="button" @click="open=!open" class="hdr-icon-btn" title="{{ __('frontend.header.theme') }}" :aria-expanded="open.toString()">
              <span class="hdr-theme-dot" style="background: {{ $currentThemePrimary }}"></span>
              <span class="hidden lg:inline">{{ $currentThemeTitle }}</span>
              <i class="fas fa-chevron-down" style="font-size:.58rem; opacity:.55"></i>
            </button>

            <div x-cloak x-show="open" x-transition class="hdr-lang-menu">
              @foreach($themeList as $themeOption)
                <a href="{{ route('frontend.theme.set', $themeOption->slug) }}"
                   class="hdr-lang-item {{ $themeOption->slug === $currentThemeSlug ? 'is-active' : '' }}">
                  <span class="hdr-theme-dot" style="background: {{ $themeOption->primary }}"></span>
                  <span>{{ $themeOption->title }}</span>
                  @if($themeOption->is_default)
                    <span class="hdr-theme-note">{{ __('frontend.header.theme_site') }}</span>
                  @endif
                  @if($themeOption->slug === $currentThemeSlug)
                    <i class="fas fa-check" style="margin-left:auto; font-size:.7rem"></i>
                  @endif
                </a>
              @endforeach

              @if($currentThemeSlug)
                <a href="{{ route('frontend.theme.set', 'reset') }}" class="hdr-lang-item hdr-theme-reset">
                  <i class="fas fa-rotate-left" style="font-size:.7rem; opacity:.6"></i>
                  <span>{{ __('frontend.header.theme_default') }}</span>
                </a>
              @endif
            </div>
          </div>
        @endif

        @if ($hasProducts)
          <a href="{{ route('cart.index') }}" class="hdr-pill" title="{{ __('frontend.header.cart') }}">
            <span class="cart-ico">
              <span class="cart-ico__glyph"><i class="fas fa-cart-shopping"></i></span>
              <span id="cart-count" class="cart-ico__badge {{ $cartCount == 0 ? 'hidden' : '' }}">{{ $cartCount }}</span>
            </span>
            <span class="hidden lg:inline">{{ __('frontend.header.cart') }}</span>
          </a>
        @endif

        @auth
          <a href="{{ route('dashboard') }}" class="hdr-pill" title="{{ __('frontend.header.account_title') }}">
            <i class="fas fa-user"></i><span class="hidden md:inline">{{ __('frontend.header.account') }}</span>
          </a>

          @if (($user->is_admin ?? false))
            <a href="{{ url('/admin/modules') }}" class="hdr-pill hdr-pill--accent" title="{{ __('frontend.header.admin_title') }}">
              <i class="fas fa-gauge-high"></i><span class="hidden md:inline">{{ __('frontend.header.admin') }}</span>
            </a>
          @endif

          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="hdr-pill hdr-pill--danger" title="{{ __('frontend.header.logout') }}">
              <i class="fas fa-right-from-bracket"></i><span class="hidden md:inline">{{ __('frontend.header.logout') }}</span>
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="hdr-pill" title="{{ __('frontend.header.login') }}">
            <i class="fas fa-right-to-bracket"></i><span class="hidden md:inline">{{ __('frontend.header.login') }}</span>
          </a>
          <a href="{{ route('register') }}" class="hdr-pill hdr-pill--accent" title="{{ __('frontend.header.register') }}">
            <i class="fas fa-user-plus"></i><span class="hidden md:inline">{{ __('frontend.header.register') }}</span>
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
        <form method="GET" action="{{ route('frontend.search') }}" class="w-full md:flex-1 md:max-w-xl md:ml-8">
          <div class="hdr-search-row">
            <div class="hdr-search relative flex-1">
              <span class="hdr-search-ico" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                </svg>
              </span>
              <input type="text" name="q" value="{{ request('q') }}" class="hdr-search-input" placeholder="{{ __('frontend.header.search') }}">
            </div>
            <button type="submit" class="hdr-search-go">{{ __('frontend.header.search_action') }}</button>
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

    /* Переключатель языка */
    [x-cloak]{ display:none !important; }
    .hdr-lang-menu{ position:absolute; right:0; top:calc(100% + .45rem); z-index:1000; min-width:11.5rem; padding:.3rem;
        background:rgba(255,255,255,.96); -webkit-backdrop-filter:blur(12px); backdrop-filter:blur(12px);
        border:1px solid rgba(17,24,39,.08); border-radius:11px; box-shadow:0 16px 40px -14px rgba(17,24,39,.3); }
    :root.dark .hdr-lang-menu{ background:rgba(15,23,42,.96); border-color:rgba(255,255,255,.08); }
    .hdr-lang-item{ display:flex; align-items:center; gap:.6rem; padding:.5rem .55rem; border-radius:8px; font-size:.83rem;
        color:#374151; text-decoration:none; transition:background .12s ease, color .12s ease; }
    :root.dark .hdr-lang-item{ color:#d1d5db; }
    .hdr-lang-item:hover{ background:rgba(99,102,241,.1); color:#4f46e5; }
    :root.dark .hdr-lang-item:hover{ background:rgba(99,102,241,.2); color:#c7d2fe; }
    .hdr-lang-item.is-active{ color:#4f46e5; font-weight:500; }
    :root.dark .hdr-lang-item.is-active{ color:#c7d2fe; }
    /* Переключатель темы: кружок-превью в цвете акцента темы */
    .hdr-theme-dot{ width:.85rem; height:.85rem; border-radius:999px; flex:0 0 auto;
        border:1px solid rgba(17,24,39,.15); box-shadow:0 1px 2px rgba(17,24,39,.15); }
    .hdr-theme-note{ font-size:.62rem; color:#9ca3af; letter-spacing:.02em; }
    .hdr-theme-reset{ border-top:1px solid rgba(17,24,39,.08); margin-top:.2rem; padding-top:.55rem; color:#6b7280; }
    :root.dark .hdr-theme-reset{ border-color:rgba(255,255,255,.08); }

    .hdr-lang-code{ display:inline-flex; align-items:center; justify-content:center; min-width:1.75rem; height:1.4rem;
        padding:0 .3rem; border-radius:6px; background:rgba(99,102,241,.13); color:#4338ca; font-size:.62rem;
        font-weight:700; letter-spacing:.03em; flex:0 0 auto; }
    :root.dark .hdr-lang-code{ background:rgba(99,102,241,.24); color:#c7d2fe; }
    /* Маленький аккуратный флаг (инлайн-SVG) */
    .flag{ width:1.4rem; height:1rem; border-radius:3px; display:inline-block; flex:0 0 auto; overflow:hidden;
        vertical-align:middle; box-shadow:0 0 0 1px rgba(17,24,39,.12); }
    :root.dark .flag{ box-shadow:0 0 0 1px rgba(255,255,255,.18); }
    .hdr-lang .hdr-icon-btn .flag{ width:1.3rem; height:.92rem; }

    /* Поиск */
    /* Поле и кнопка — одной высоты, кнопка рядом с полем (align-items:stretch) */
    .hdr-search-row{ display:flex; align-items:stretch; gap:.5rem; width:100%; }
    .hdr-search-input{ width:100%; height:100%; padding:.55rem .9rem .55rem 2.3rem; border-radius:10px; font-size:.85rem; line-height:1.2;
        border:1px solid rgba(17,24,39,.14); background:rgba(255,255,255,.7); color:#111827;
        transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    :root.dark .hdr-search-input{ background:rgba(30,41,59,.7); border-color:rgba(255,255,255,.12); color:#f3f4f6; }
    .hdr-search-input::placeholder{ color:#9ca3af; }
    .hdr-search-input:focus{ outline:none; border-color:#818cf8; box-shadow:0 0 0 3px rgba(99,102,241,.18); background:#fff; }
    :root.dark .hdr-search-input:focus{ background:rgba(30,41,59,.96); }
    /* Лупа слева — только значок: отправляет форму кнопка «Найти» справа */
    .hdr-search-ico{ position:absolute; left:.6rem; top:50%; transform:translateY(-50%); z-index:2;
        display:inline-flex; color:#9ca3af; pointer-events:none; }
    /* Кнопка отправки рядом с полем, той же высоты */
    .hdr-search-go{ flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center;
        padding:0 1.05rem; border:0; border-radius:10px; white-space:nowrap;
        background:var(--fx-grad,#6366f1); color:#fff; font-size:.82rem; font-weight:600; line-height:1.2;
        cursor:pointer; box-shadow:0 8px 18px -10px rgba(99,102,241,.8);
        transition:filter .15s ease, transform .15s ease; }
    .hdr-search-go:hover{ filter:brightness(1.07); transform:translateY(-1px); }
    .hdr-search-go:active{ transform:translateY(0); }

    /* Корзина */
    .cart-ico{ position:relative; display:inline-flex; align-items:center; }
    .cart-ico__glyph{ display:inline-flex; line-height:1; font-size:1rem; }
    .cart-ico__badge{ position:absolute; top:-7px; left:-7px; min-width:1.05rem; height:1.05rem; padding:0 .18rem;
        display:flex; align-items:center; justify-content:center; font-size:.68rem; line-height:1; color:#fff;
        background:var(--fx-a,#6366f1); border-radius:999px; box-shadow:0 0 0 2px #fff; }
    :root.dark .cart-ico__badge{ box-shadow:0 0 0 2px #0f172a; }
  </style>
</header>
