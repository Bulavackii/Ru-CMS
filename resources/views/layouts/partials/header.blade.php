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
  {{-- Фоновая картинка сайта проступает в шапке. Слой рисуется ПОВЕРХ
       заливки стекла (см. .hdr-glass::before), иначе 82% непрозрачности
       стекла гасили узор почти полностью. --}}

  <div class="hdr-glass relative z-[999] transition-colors duration-200">

    {{-- ═══════════ Ряд 1: логотип + действия ═══════════ --}}
    <div class="hdr-row1 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">

      {{-- ЛОГОТИП

           Своя картинка из раздела «Темы» имеет приоритет — эта ветка не
           менялась. Но текстовый знак теперь выводится ВСЕГДА и прячется
           лишь при живой картинке: раньше сломанная ссылка на логотип
           оставляла шапку пустой, потому что onerror просто убирал
           изображение и показать было нечего. --}}
      <a href="{{ url('/') }}" class="hdr-logo {{ $logoWrapCls }}" aria-label="{{ __('frontend.header.home_aria') }}">
        @if($logoAbs)
          {{-- Логотип шапки виден сразу и участвует в отрисовке первого
               экрана, поэтому грузится без отсрочки и в приоритете. --}}
          <img src="{{ $logoAbs }}" alt="{{ __('frontend.header.logo_alt') }}"
               fetchpriority="high" decoding="async"
               style="width: {{ $logoW }}; max-width:100%; height:auto;" class="hdr-logo-img"
               onerror="const m = this.closest('.hdr-logo').querySelector('.hdr-logo-mark'); this.remove(); if (m) m.hidden = false;">
        @endif

        <span class="hdr-logo-mark" @if($logoAbs) hidden @endif>
          <span class="hdr-logo-badge" aria-hidden="true">RU</span>
          <span class="hdr-logo-text">
            <span class="hdr-logo-name">CMS</span>
            <span class="hdr-logo-sub">{{ __('frontend.header.tagline') }}</span>
          </span>
        </span>
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

        {{-- Переключателя тем в шапке сайта нет: оформление задаётся в
             панели (Темы → Применить), и выбор администратора применяется
             сразу и к панели, и к сайту. --}}

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
            <svg class="hdr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M4.6 20a7.4 7.4 0 0 1 14.8 0"/></svg><span class="hidden md:inline">{{ __('frontend.header.account') }}</span>
          </a>

          @if (($user->is_admin ?? false))
            <a href="{{ route('admin.dashboard') }}" class="hdr-pill hdr-pill--accent" title="{{ __('frontend.header.admin_title') }}">
              <svg class="hdr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2.2"/><circle cx="9" cy="17" r="2.2"/></svg><span class="hidden md:inline">{{ __('frontend.header.admin') }}</span>
            </a>
          @endif

          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="hdr-pill hdr-pill--danger" title="{{ __('frontend.header.logout') }}">
              <svg class="hdr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4H6.5A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20H14"/><path d="M17 8.5 20.5 12 17 15.5M20.5 12H10"/></svg><span class="hidden md:inline">{{ __('frontend.header.logout') }}</span>
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="hdr-pill" title="{{ __('frontend.header.login') }}">
            <svg class="hdr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4h7.5A1.5 1.5 0 0 1 19 5.5v13a1.5 1.5 0 0 1-1.5 1.5H10"/><path d="M6.5 8.5 3 12l3.5 3.5M3 12h10"/></svg><span class="hidden md:inline">{{ __('frontend.header.login') }}</span>
          </a>
          <a href="{{ route('register') }}" class="hdr-pill hdr-pill--accent" title="{{ __('frontend.header.register') }}">
            <svg class="hdr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="3.4"/><path d="M3.5 20a6.5 6.5 0 0 1 13 0"/><path d="M18.5 8.5v5M21 11h-5"/></svg><span class="hidden md:inline">{{ __('frontend.header.register') }}</span>
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
    /* Стекло: заливка полупрозрачная, чтобы фон сайта читался сквозь
       шапку, плюс размытие и лёгкая насыщенность — так «стекло» выглядит
       стеклом, а не просто бледной плашкой. */
    .hdr-glass{ position:relative; background:rgba(255,255,255,.62);
        -webkit-backdrop-filter:blur(18px) saturate(180%);
        backdrop-filter:blur(18px) saturate(180%);
        border-bottom:1px solid rgba(255,255,255,.5);
        box-shadow:0 1px 24px rgba(15,23,42,.06); }

    /* Фоновая картинка сайта, проступающая сквозь шапку.
       Лежит НАД заливкой стекла: под ней узор гасился почти полностью.
       pointer-events:none — слой не должен перехватывать клики по меню. */
    .hdr-glass::before{ content:''; position:absolute; inset:0; z-index:0;
        background-image:var(--bg-image); background-repeat:repeat; background-size:auto;
        opacity:.85; pointer-events:none; }

    /* Содержимое шапки — над подложкой. */
    .hdr-glass > *{ position:relative; z-index:1; }
    :root.dark .hdr-glass{ background:rgba(15,23,42,.62); border-bottom-color:rgba(255,255,255,.08); }

    /* Тёмная ТЕМА — отдельный класс от системного тёмного режима, и
       правило выше про него не знает. Без этих строк на тёмной теме
       шапка светилась узором так же, как светился подвал. */
    body.fx-theme-dark .hdr-glass::after{ opacity:.14; }
    body.fx-theme-dark .hdr-glass{
        background:color-mix(in srgb, var(--color-header) 72%, transparent);
        border-bottom-color:rgba(255,255,255,.10);
    }
    body.fx-theme-dark .hdr-glass a,
    body.fx-theme-dark .hdr-glass span,
    body.fx-theme-dark .hdr-glass button{ color:var(--color-text); }

    /* Логотип.
       Скругления тут не задаются: на сайте прямые углы включены глобально
       (body.fx-sharp с important), и прежний border-radius у знака был
       мёртвым кодом — он не применялся никогда. */
    .hdr-logo{ display:inline-flex; align-items:center; gap:.6rem; text-decoration:none; }
    .hdr-logo-img{ display:block; }
    .hdr-logo-mark{ display:inline-flex; align-items:center; gap:.6rem; }
    .hdr-logo-mark[hidden]{ display:none; }

    /* Знак: квадрат с буквами и тонкой внутренней подсветкой по верхней
       грани — она даёт объём, которого не хватало плоской заливке. */
    .hdr-logo-badge{ position:relative; width:2.4rem; height:2.4rem; flex:0 0 auto;
        display:inline-flex; align-items:center; justify-content:center;
        background:var(--fx-grad,#6366f1); color:#fff;
        font-weight:800; font-size:.85rem; letter-spacing:.06em;
        box-shadow:0 10px 20px -10px rgba(99,102,241,.75);
        transition:transform .18s ease, box-shadow .18s ease; }
    .hdr-logo-badge::after{ content:''; position:absolute; inset:0;
        border-top:1px solid rgba(255,255,255,.35);
        border-left:1px solid rgba(255,255,255,.18); pointer-events:none; }
    .hdr-logo:hover .hdr-logo-badge{ transform:translateY(-1px);
        box-shadow:0 14px 24px -10px rgba(99,102,241,.85); }

    .hdr-logo-text{ display:flex; flex-direction:column; line-height:1.05; }

    /* Заливка градиентом по контуру букв. Цвет остаётся запасным на случай,
       если браузер не умеет обрезать фон по тексту. */
    .hdr-logo-name{ font-weight:800; font-size:1.45rem; letter-spacing:-.02em; color:#6366f1;
        background:var(--fx-grad,#6366f1); -webkit-background-clip:text; background-clip:text;
        -webkit-text-fill-color:transparent; }

    /* Подпись набрана в разрядку прописными: так короткая строка читается
       как часть знака, а не как обрывок текста под ним. */
    .hdr-logo-sub{ font-size:.58rem; font-weight:600; letter-spacing:.14em;
        text-transform:uppercase; color:#94a3b8; margin-top:2px; white-space:nowrap; }
    :root.dark .hdr-logo-sub{ color:#9ca3af; }

    /* На узком экране остаётся только знак и название — подпись не влезает
       в строку рядом с кнопками и переносила бы шапку. */
    @media (max-width:639px){
        .hdr-logo-sub{ display:none; }
        .hdr-logo-badge{ width:2.1rem; height:2.1rem; font-size:.78rem; }
        .hdr-logo-name{ font-size:1.25rem; }
    }

    /* Пилюли-действия */
    .hdr-actions .hdr-pill, .hdr-actions .hdr-icon-btn{
        display:inline-flex; align-items:center; gap:.4rem; padding:.42rem .72rem; border-radius:9px;
        font-size:.82rem; font-weight:500; line-height:1; color:#374151; background:transparent; border:0;
        cursor:pointer; text-decoration:none; white-space:nowrap; position:relative;
        transition:background .14s ease, color .14s ease, filter .14s ease; }
    :root.dark .hdr-actions .hdr-pill, :root.dark .hdr-actions .hdr-icon-btn{ color:#d1d5db; }
    .hdr-actions .hdr-icon-btn{ padding:.42rem .58rem; }
    .hdr-actions .hdr-pill i, .hdr-actions .hdr-icon-btn i{ font-size:1rem; line-height:1; }
    /* Ховер — полоса снизу, как у пунктов меню слева, а не заливка
       подложкой: раньше действия справа подсвечивались прямоугольником и
       вели себя иначе, чем навигация. Цвет берётся из активной темы, так
       что подчёркивание меняется вместе с оформлением. */
    .hdr-actions .hdr-pill::after, .hdr-actions .hdr-icon-btn::after{
        content:''; position:absolute; left:.5rem; right:.5rem; bottom:2px; height:2px;
        background:var(--color-primary,#6366f1);
        transform:scaleX(0); transform-origin:center;
        transition:transform .22s ease; }
    .hdr-actions .hdr-pill:hover, .hdr-actions .hdr-icon-btn:hover{ color:var(--color-primary,#6366f1); }
    .hdr-actions .hdr-pill:hover::after, .hdr-actions .hdr-icon-btn:hover::after,
    .hdr-actions .hdr-pill:focus-visible::after, .hdr-actions .hdr-icon-btn:focus-visible::after{
        transform:scaleX(1); }
    :root.dark .hdr-actions .hdr-pill:hover, :root.dark .hdr-actions .hdr-icon-btn:hover{ color:#c7d2fe; }

    /* Значки — штриховые, поэтому размер задаётся, а цвет наследуется от
       самой кнопки и меняется вместе с ней. */
    .hdr-actions .hdr-ico{ width:1.05rem; height:1.05rem; flex:0 0 auto; }
    /* Залитая кнопка остаётся залитой: полоса на градиенте не читается. */
    .hdr-actions .hdr-pill--accent{ background:var(--fx-grad,#6366f1); color:#fff; box-shadow:0 8px 18px -10px rgba(99,102,241,.7); }
    .hdr-actions .hdr-pill--accent::after{ display:none; }
    .hdr-actions .hdr-pill--accent:hover{ background:var(--fx-grad,#6366f1); color:#fff; filter:brightness(1.08); }

    /* Выход подчёркивается своим цветом — он тут смысловой. */
    .hdr-actions .hdr-pill--danger{ color:#e11d48; }
    .hdr-actions .hdr-pill--danger::after{ background:#e11d48; }
    .hdr-actions .hdr-pill--danger:hover{ color:#e11d48; }
    :root.dark .hdr-actions .hdr-pill--danger:hover{ color:#fb7185; }

    /* Переключатель языка */
    [x-cloak]{ display:none !important; }
    /* Верхняя строка шапки поднята над нижней.

       Обе строки получают z-index:1 от правила стеклянной шапки ниже, обе
       создают собственный контекст наложения — и при равном уровне
       выигрывает та, что идёт в разметке позже, то есть строка поиска. Из-за
       этого выпадающий список языков был заперт в контексте своей строки:
       его собственный z-index:1000 не мог поднять список над полем поиска,
       и у меню было видно только нижнюю половину, а верхний пункт уходил
       под поле. Поднимать сам переключатель бесполезно по той же причине —
       уровень нужен строке. */
    .hdr-glass > .hdr-row1{ z-index:2; }
    .hdr-lang{ z-index:1001; }
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
