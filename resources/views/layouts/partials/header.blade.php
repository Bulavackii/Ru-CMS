{{-- Шапка сайта. Подключается через @include из layouts.frontend, а не как
     компонент, поэтому props-объявления здесь нет: переменные надо брать
     явно. Раньше стояло @props(['user' => auth()->user()]), и признак
     администратора читался из $user — то есть из переменной, которую могла
     подставить и страница-родитель (у @include видимость данных общая).
     Кнопка «Админка» зависела бы от того, чей $user попал во вьюху. --}}
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
{{-- x-data на всей шапке: на узких экранах меню и поиск живут в панели,
     которую раскрывает кнопка-бургер. Разметка меню при этом ОДНА — на
     широких экранах панель просто всегда показана (см. .hdr-row2). --}}
{{-- Признак «меню раскрыто» выносится на body: плавающие кнопки (виджет
     спецвозможностей, возврат наверх) живут в другом конце документа, и
     дотянуться до них селектором от шапки нельзя. --}}
<header class="fx-header relative text-sm z-10" x-data="{ navOpen: false }"
        x-effect="document.body.classList.toggle('hdr-nav-open', navOpen)"
        @keydown.escape.window="navOpen = false">
  {{-- Фоновая картинка сайта проступает в шапке. Слой рисуется ПОВЕРХ
       заливки стекла (см. .hdr-glass::before), иначе 82% непрозрачности
       стекла гасили узор почти полностью. --}}

  <div class="hdr-glass relative z-[999] transition-colors duration-200">

    {{-- ═══════════ Ряд 1: логотип + действия ═══════════ --}}
    <div class="hdr-row1 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 flex items-center justify-between gap-3 sm:gap-4">

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
               style="max-width: min({{ $logoW }}, 100%);" class="hdr-logo-img"
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
        // Признак один на весь проект (см. site_has_products): здесь и во
        // фрагменте оформления он считался по-своему и без проверки
        // публикации — черновик товара зажигал корзину.
        $hasProducts = site_has_products();
      @endphp

      {{-- ДЕЙСТВИЯ --}}
      <div class="hdr-actions flex items-center flex-wrap justify-center gap-1.5">

        {{-- Переключатель языка: тянет ВСЕ доступные локали (resources/lang),
             переключает через frontend.locale.set → session app_locale. --}}
        @php
          $langNames = ['ru'=>'Русский','en'=>'English','be'=>'Беларуская','kk'=>'Қазақша','de'=>'Deutsch','fr'=>'Français','it'=>'Italiano'];
          $curLocale = app()->getLocale();
        @endphp
        {{-- Список языков и раскрытое меню — взаимоисключающие. На телефоне
             список выпадает ровно поверх первых пунктов меню и прячет их;
             поэтому открытие одного закрывает другое. --}}
        <div x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false"
             @hdr-close-lang.window="open=false" class="hdr-lang relative">
          <button type="button" @click="open=!open; if (open) navOpen = false" class="hdr-icon-btn" title="{{ __('frontend.header.language') }}" :aria-expanded="open.toString()">
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

          {{-- Кнопка панели — только тем, кого туда действительно пустят.
               Признак берём тот же, что проверяет AdminMiddleware, и у
               ВОШЕДШЕГО пользователя, а не у переменной из вьюхи. --}}
          @if (auth()->user()?->is_admin)
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

        {{-- Бургер: только на телефонах и планшетах в портрете. На широких
             экранах меню и поиск и так стоят отдельной строкой. --}}
        <button type="button" class="hdr-burger" x-on:click="navOpen = !navOpen; $dispatch('hdr-close-lang')"
                :aria-expanded="navOpen ? 'true' : 'false'" aria-controls="hdr-nav-panel"
                :aria-label="navOpen ? @js(__('frontend.header.menu_close')) : @js(__('frontend.header.menu_open'))">
          <span class="hdr-burger__box" :class="navOpen ? 'is-open' : ''" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        </button>
      </div>
    </div>

    {{-- ═══════════ Ряд 2: навигация (модуль Меню) + поиск ═══════════ --}}
    <div id="hdr-nav-panel" class="hdr-row2 border-t border-gray-200 dark:border-gray-700"
         :class="navOpen ? 'is-open' : ''">
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
    /* Шапка на тёмной теме — ОТДЕЛЬНАЯ поверхность, а не тот же тон поверх
       такого же фона. Раньше она была цветом страницы при семидесяти двух
       процентах непрозрачности: формально тёмная, а на экране сливалась —
       границы не видно, шапка и содержимое читались одним полотном.

       Берём ту же поверхность, что у карточек (она на семь процентов светлее
       фона), плюс заметную нижнюю грань и тень под ней. Разница небольшая,
       но глазу хватает, чтобы прочесть край. */
    body.fx-theme-dark .hdr-glass{
        /* Стекло, как у подвала: та же непрозрачность заливки (62%), то же
           размытие с подъёмом насыщенности. Фон сайта читается сквозь шапку.

           Сплошной она была недолго и по одной причине — УЗОР. Его рисует
           ::before ПОВЕРХ заливки при непрозрачности .85, и сквозь
           полупрозрачную шапку он высветлял всю полосу до светло-серой,
           отчего надписи меню тонули. Но подвал решает ровно это же и не
           отказываясь от стекла: он приглушает узор до .14. Тот же приём
           ниже — и стекло возвращается без потери читаемости.

           Нижняя грань и тень остаются: без них край шапки на тёмной теме
           не читался — она сливалась со страницей в одно полотно. */
        background:color-mix(in srgb, var(--color-header) 62%, transparent);
        border-bottom:1px solid var(--surface-bd);
        box-shadow:0 8px 24px -14px rgba(0,0,0,.85);
        -webkit-backdrop-filter:blur(18px) saturate(180%);
        backdrop-filter:blur(18px) saturate(180%);
    }

    /* Узор в шапке рисует ::before, а НЕ ::after (в подвале — наоборот).
       Приглушаем до той же величины, что и подвал: сквозь стекло он должен
       угадываться, а не забивать полосу. */
    body.fx-theme-dark .hdr-glass::before{ opacity:.14; }
    body.fx-theme-dark .hdr-glass a,
    body.fx-theme-dark .hdr-glass span,
    body.fx-theme-dark .hdr-glass button{ color:var(--color-text); }

    /* Поле поиска было белым — на тёмной шапке светилось ярким пятном. */
    body.fx-theme-dark .hdr-search-input{
        color:var(--color-text);
        background:var(--surface);
        border:1px solid var(--surface-bd);
    }
    body.fx-theme-dark .hdr-search-input:focus{
        border-color:var(--color-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--color-primary) 22%, transparent);
    }
    body.fx-theme-dark .hdr-search-input::placeholder{ color:var(--surface-dim); }

    /* Логотип.
       Скругления тут не задаются: на сайте прямые углы включены глобально
       (body.fx-sharp с important), и прежний border-radius у знака был
       мёртвым кодом — он не применялся никогда. */
    .hdr-logo{ display:inline-flex; align-items:center; gap:.6rem; text-decoration:none; }
    /* Высота знака РАВНА высоте плашки текстовой марки (2.4rem), поэтому
       шапка одинакова на всех темах — замер: 124px и там, и там. Настройка «Ширина» в теме задаёт
       ПРЕДЕЛ по ширине, а не жёсткий размер: квадратный знак при width:120px
       становился 120px и в высоту — шапка вырастала вдвое против темы по
       умолчанию. Пропорции сохраняет object-fit. */
    .hdr-logo-img{ display:block; height:auto; max-height:2.4rem; object-fit:contain; }
    .hdr-logo-mark{ display:inline-flex; align-items:center; gap:.6rem; }
    .hdr-logo-mark[hidden]{ display:none; }

    /* Знак: квадрат с буквами и тонкой внутренней подсветкой по верхней
       грани — она даёт объём, которого не хватало плоской заливке. */
    .hdr-logo-badge{ position:relative; width:2.4rem; height:2.4rem; flex:0 0 auto;
        display:inline-flex; align-items:center; justify-content:center;
        background:var(--fx-grad,#6366f1); color:#fff;
        font-weight:800; font-size:.85rem; letter-spacing:.06em;
        box-shadow:0 10px 20px -10px color-mix(in srgb, var(--color-primary, #6366f1) 75%, transparent);
        transition:transform .18s ease, box-shadow .18s ease; }
    .hdr-logo-badge::after{ content:''; position:absolute; inset:0;
        border-top:1px solid rgba(255,255,255,.35);
        border-left:1px solid rgba(255,255,255,.18); pointer-events:none; }
    .hdr-logo:hover .hdr-logo-badge{ transform:translateY(-1px);
        box-shadow:0 14px 24px -10px color-mix(in srgb, var(--color-primary, #6366f1) 85%, transparent); }

    .hdr-logo-text{ display:flex; flex-direction:column; line-height:1.05; }

    /* Заливка градиентом по контуру букв. Цвет остаётся запасным на случай,
       если браузер не умеет обрезать фон по тексту. */
    .hdr-logo-name{ font-weight:800; font-size:1.45rem; letter-spacing:-.02em; color:var(--color-primary, #6366f1);
        background:var(--fx-grad,#6366f1); -webkit-background-clip:text; background-clip:text;
        -webkit-text-fill-color:transparent; }

    /* Подпись набрана в разрядку прописными: так короткая строка читается
       как часть знака, а не как обрывок текста под ним. */
    .hdr-logo-sub{ font-size:.58rem; font-weight:600; letter-spacing:.14em;
        text-transform:uppercase; color:var(--surface-dim,#94a3b8); margin-top:2px; white-space:nowrap; }
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
        display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .5rem; border-radius:9px;
        font-size:.82rem; font-weight:500; line-height:1; color:var(--surface-ink,#374151); background:transparent; border:0;
        cursor:pointer; text-decoration:none; white-space:nowrap; position:relative;
        transition:background .14s ease, color .14s ease, filter .14s ease; }
    :root.dark .hdr-actions .hdr-pill, :root.dark .hdr-actions .hdr-icon-btn{ color:#d1d5db; }
    .hdr-actions .hdr-icon-btn{ padding:.42rem .42rem; }
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
    .hdr-actions .hdr-pill--accent{ background:var(--fx-grad,#6366f1); color:#fff; box-shadow:0 8px 18px -10px color-mix(in srgb, var(--color-primary, #6366f1) 70%, transparent); }
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
        color:var(--surface-ink,#374151); text-decoration:none; transition:background .12s ease, color .12s ease; }
    :root.dark .hdr-lang-item{ color:#d1d5db; }
    .hdr-lang-item:hover{ background:color-mix(in srgb, var(--color-primary, #6366f1) 10%, transparent); color:var(--color-primary, #4f46e5); }
    :root.dark .hdr-lang-item:hover{ background:color-mix(in srgb, var(--color-primary, #6366f1) 20%, transparent); color:#c7d2fe; }
    .hdr-lang-item.is-active{ color:var(--color-primary, #4f46e5); font-weight:500; }

    /* Выпадающий список языков на тёмной теме. Тёмный вид у него был описан
       только под системный тёмный режим (:root.dark), поэтому на тёмной ТЕМЕ
       панель оставалась белой, а подписи на ней — светлыми: список
       открывался пустым белым прямоугольником.

       Цвет выбранного языка берётся из темы: прибитый фиолетовый смотрелся
       чужим на мятной или янтарной теме. */
    body.fx-theme-dark .hdr-lang-menu{
        background:var(--surface);
        border-color:var(--surface-bd);
    }
    body.fx-theme-dark .hdr-lang-item{ color:var(--color-text); }
    body.fx-theme-dark .hdr-lang-item:hover{
        color:var(--color-bg);
        background:var(--color-primary);
    }
    body.fx-theme-dark .hdr-lang-item.is-active{ color:var(--color-primary); }
    :root.dark .hdr-lang-item.is-active{ color:#c7d2fe; }
    /* Переключатель темы: кружок-превью в цвете акцента темы */
    .hdr-theme-dot{ width:.85rem; height:.85rem; border-radius:999px; flex:0 0 auto;
        border:1px solid rgba(17,24,39,.15); box-shadow:0 1px 2px rgba(17,24,39,.15); }
    .hdr-theme-note{ font-size:.62rem; color:var(--surface-dim,#9ca3af); letter-spacing:.02em; }
    .hdr-theme-reset{ border-top:1px solid rgba(17,24,39,.08); margin-top:.2rem; padding-top:.55rem; color:var(--surface-mute,#6b7280); }
    :root.dark .hdr-theme-reset{ border-color:rgba(255,255,255,.08); }

    .hdr-lang-code{ display:inline-flex; align-items:center; justify-content:center; min-width:1.75rem; height:1.4rem;
        padding:0 .3rem; border-radius:6px; background:color-mix(in srgb, var(--color-primary, #6366f1) 13%, transparent); color:var(--color-primary, #4338ca); font-size:.62rem;
        font-weight:700; letter-spacing:.03em; flex:0 0 auto; }
    :root.dark .hdr-lang-code{ background:color-mix(in srgb, var(--color-primary, #6366f1) 24%, transparent); color:#c7d2fe; }
    /* Маленький аккуратный флаг (инлайн-SVG) */
    .flag{ width:1.4rem; height:1rem; border-radius:3px; display:inline-block; flex:0 0 auto; overflow:hidden;
        vertical-align:middle; box-shadow:0 0 0 1px rgba(17,24,39,.12); }
    :root.dark .flag{ box-shadow:0 0 0 1px rgba(255,255,255,.18); }
    .hdr-lang .hdr-icon-btn .flag{ width:1.3rem; height:.92rem; }

    /* Поиск */
    /* Поле и кнопка — одной высоты, кнопка рядом с полем (align-items:stretch) */
    .hdr-search-row{ display:flex; align-items:stretch; gap:.5rem; width:100%; }
    .hdr-search-input{ width:100%; height:100%; padding:.55rem .9rem .55rem 2.3rem; border-radius:10px; font-size:.85rem; line-height:1.2;
        border:1px solid rgba(17,24,39,.14); background:rgba(255,255,255,.7); color:var(--surface-ink,#111827);
        transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    :root.dark .hdr-search-input{ background:rgba(30,41,59,.7); border-color:rgba(255,255,255,.12); color:#f3f4f6; }
    .hdr-search-input::placeholder{ color:var(--surface-dim,#9ca3af); }
    .hdr-search-input:focus{ outline:none; border-color:var(--color-primary, #818cf8); box-shadow:0 0 0 3px color-mix(in srgb, var(--color-primary, #6366f1) 18%, transparent); background:var(--surface,#fff); }
    :root.dark .hdr-search-input:focus{ background:rgba(30,41,59,.96); }
    /* Лупа слева — только значок: отправляет форму кнопка «Найти» справа */
    .hdr-search-ico{ position:absolute; left:.6rem; top:50%; transform:translateY(-50%); z-index:2;
        display:inline-flex; color:var(--surface-dim,#9ca3af); pointer-events:none; }
    /* Кнопка отправки рядом с полем, той же высоты */
    .hdr-search-go{ flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center;
        padding:0 1.05rem; border:0; border-radius:10px; white-space:nowrap;
        background:var(--fx-grad,#6366f1); color:#fff; font-size:.82rem; font-weight:600; line-height:1.2;
        cursor:pointer; box-shadow:0 8px 18px -10px color-mix(in srgb, var(--color-primary, #6366f1) 80%, transparent);
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

    /* ═════════ Телефоны и планшеты в портрете ═════════
       Шапка занимала до 520px высоты на экране в 896: логотип, действия,
       пять пунктов меню и поиск вставали друг под друга. Теперь первая
       строка одна, а меню с поиском раскрывает бургер. Разметка меню при
       этом не дублируется — переключается только показ. */
    .hdr-burger{ display:none; }

    @media (max-width: 1024px), (max-height: 500px){
      /* Панель меню закрыта, пока её не раскрыли кнопкой. */
      .hdr-row2{ display:none; }
      .hdr-row2.is-open{ display:block; }

      /* Тот же приём, что у пилюль рядом: рисуется на 36, нажимается на 44
         (см. ::before ниже). Иначе бургер один остался бы крупнее всех и
         тянул ряд вверх. */
      .hdr-burger{
        position:relative;
        display:inline-flex; align-items:center; justify-content:center;
        width:36px; height:36px; flex:0 0 auto; margin-left:.15rem;
        border:1px solid var(--surface-bd,#e3e6ee); border-radius:12px;
        background:var(--surface,#fff); color:var(--surface-ink,#111827); cursor:pointer;
      }
      .hdr-burger__box{ position:relative; display:block; width:20px; height:14px; }
      .hdr-burger__box span{
        position:absolute; left:0; width:100%; height:2px; border-radius:2px;
        background:currentColor; transition:transform .2s ease, opacity .15s ease, top .2s ease;
      }
      .hdr-burger__box span:nth-child(1){ top:0; }
      .hdr-burger__box span:nth-child(2){ top:6px; }
      .hdr-burger__box span:nth-child(3){ top:12px; }
      /* Раскрытое состояние — крестик: он честно говорит, что нажатие закроет. */
      .hdr-burger__box.is-open span:nth-child(1){ top:6px; transform:rotate(45deg); }
      .hdr-burger__box.is-open span:nth-child(2){ opacity:0; }
      .hdr-burger__box.is-open span:nth-child(3){ top:6px; transform:rotate(-45deg); }

      /* Логотип не должен выдавливать действия за край. */
      .hdr-row1{ padding-top:.55rem; padding-bottom:.55rem; gap:.5rem; }
      .hdr-logo{ min-width:0; flex:0 1 auto; min-height:44px; }
      .hdr-logo-img{ max-height:2rem; }
      .hdr-logo-sub{ display:none; }

      /* Зоны нажатия — не меньше 44px по обеим сторонам: пальцем в кружок
         33×30 не попасть, а именно такими пилюли и были.

         ⚠️ Но САМА кнопка при этом рисуется на 36. Пилюли в 44 по высоте
         превращали ряд действий в полосу тяжёлых плиток — владелец
         справедливо назвал их громоздкими. Недостающие пиксели добирает
         прозрачный слой ::before: он выходит за края кнопки и ловит
         нажатие. Так вид лёгкий, а палец попадает в те же 44.

         ::after занят подчёркиванием при наведении (см. выше), поэтому
         расширитель — именно ::before. */
      .hdr-actions{ flex-wrap:nowrap; gap:.1rem; }
      .hdr-actions .hdr-pill,
      .hdr-actions .hdr-icon-btn{ min-width:36px; min-height:36px;
          padding:0 .5rem; font-size:.78rem; justify-content:center; }
      .hdr-actions .hdr-pill i, .hdr-actions .hdr-icon-btn i{ font-size:.9rem; }
      .hdr-actions .hdr-ico{ width:.95rem; height:.95rem; }
      .hdr-actions .hdr-pill::before,
      .hdr-actions .hdr-icon-btn::before,
      .hdr-burger::before{
          content:''; position:absolute; inset:-4px; }
      .hdr-actions .hdr-pill::after,
      .hdr-actions .hdr-icon-btn::after{ display:none; }
      .hdr-actions .hdr-pill--accent{ box-shadow:none; }

      /* Поиск в раскрытой панели — на всю ширину, кнопка не жмётся. */
      .hdr-search-input{ height:44px; }
      .hdr-search-go{ height:44px; }

      /* Раскрытое меню — списком, а не строчками у левого края: строка на
         всю ширину, высотой 44, с тонкой линией между пунктами. Так видно,
         куда нажимать, и пункты не сливаются друг с другом. */
      /* ⚠️ Панель складывается в колонку во всём компактном режиме.
         Обёртка переключается на строку уже с 768 (`md:flex-row`), а
         компактный режим тянется до 1023 — на планшете меню и поиск
         вставали рядом, и пункты центрировались не по экрану, а по своей
         половине (центр 282 при ширине 768). */
      .hdr-row2 > div{ flex-direction:column; align-items:stretch; }

      /* Меню-панель: строка во всю ширину, содержимое по центру, тонкая
         линия между пунктами. Так это выглядит привычным мобильным меню, а
         не текстом, прижатым к краю карточки. */
      .hdr-row2 .header-nav{ width:100%; }
      .hdr-row2 form{ margin-left:0; max-width:none; }

      .hdr-row2 .header-nav .menu-link--root{
        justify-content:center; gap:.55rem;
        min-height:48px; padding:.6rem .75rem;
        border-bottom:1px solid var(--surface-bd,#eef0f5);
        letter-spacing:.04em;
      }
      .hdr-row2 .header-nav > .menu-item--root:last-child > .menu-link--root{ border-bottom:0; }

      /* Нажатый пункт подсвечивается целиком: на телефоне палец закрывает
         подпись, и отклик должен быть виден по всей строке. */
      .hdr-row2 .header-nav .menu-link--root:active{
        background:color-mix(in srgb, var(--color-primary, #6366f1) 8%, transparent);
      }

      /* Вложенные уровни тоже по центру, но подчинённость обязана читаться.
         Левый отступ на узком экране увёл бы их вбок от центрированных
         корневых пунктов, поэтому глубина показана иначе: подложкой на всю
         ветку, более узкой строкой и более тихим начертанием. Без этого
         второй и третий уровни выглядели такими же, как корневые. */
      .hdr-row2 .header-nav .submenu{
        background:color-mix(in srgb, var(--color-primary, #6366f1) 5%, transparent);
      }
      .hdr-row2 .header-nav .menu-link--l2,
      .hdr-row2 .header-nav .menu-link--l3{
        justify-content:center; min-height:44px;
        border-bottom:1px solid color-mix(in srgb, var(--color-primary, #6366f1) 8%, transparent);
      }
      .hdr-row2 .header-nav .menu-link--l2{
        padding-left:2.25rem; padding-right:2.25rem;
        font-size:.86rem; font-weight:500; text-transform:none;
      }
      .hdr-row2 .header-nav .menu-link--l3{
        padding-left:3.25rem; padding-right:3.25rem;
        font-size:.8rem; font-weight:400; opacity:.85; text-transform:none;
      }
      /* Последний пункт ветки без линии — иначе двойная черта с корневым. */
      .hdr-row2 .header-nav .submenu > .menu-item:last-child > .menu-link{ border-bottom:0; }

      /* ── Меню в несколько колонок (всё, что шире 640) ─────────────────
         Телефон в альбомной: экран широкий, но НИЗКИЙ (896×414) — колонкой
         шесть пунктов вместе с поиском не помещались, и раскрытое меню
         закрывало страницу целиком. На планшете та же беда от объёма: с
         трёхуровневым деревом меню занимало 1037 при экране 768.

         ⚠️ Сетка (`grid-template-columns`) для этого НЕ годится, хотя на
         плоском меню выглядит правильно. Ряд в сетке высотой с самую
         высокую ячейку: пункт с деревом из восьми ссылок растягивает весь
         ряд, и соседний пункт без детей висит один над 396 пикселями
         пустоты. Замер на трёхуровневом меню: высота 664 при экране 414 —
         вдвое хуже простой колонки.

         Работает раскладка в несколько колонок: она сама выравнивает их по
         высоте, а `break-inside` держит пункт вместе с его веткой. На том
         же дереве 664 → 300. */
      @media (min-width: 640px){
        .hdr-row2 .header-nav{
          /* ⚠️ display:block обязателен: многоколоночная раскладка не
             действует на flex-контейнер, а .header-nav объявлен flex.
             Без этой строки колонки молча не появляются — всё вытягивается
             в один столбец, и высота становится ещё больше. */
          display:block;
          /* Ширина колонки, а не их число: на 768 встаёт три, на 414 —
             правило вообще не действует (см. порог выше). Фиксированные
             три колонки на узком планшете сплющили бы длинные подписи. */
          columns:13rem;
          column-gap:1.5rem;
          column-rule:1px solid var(--surface-bd,#eef0f5);
        }
        /* Пункт и его подменю не должны разъезжаться по разным колонкам. */
        .hdr-row2 .header-nav > .menu-item--root{
          break-inside:avoid; -webkit-column-break-inside:avoid;
        }
        /* Строки ниже: в трёх колонках их втрое больше, а высоту экрана
           надо беречь. 44 — нижняя граница зоны нажатия, ниже нельзя. */
        .hdr-row2 .header-nav .menu-link--root{
          min-height:44px; padding:.45rem .5rem;
        }
        .hdr-row2 .header-nav .menu-link--l2,
        .hdr-row2 .header-nav .menu-link--l3{ min-height:38px; }
        /* Вложенные уровни в узкой колонке: боковые отступы съедали место
           под сам текст, а подчинённость и так читается подложкой ветки. */
        .hdr-row2 .header-nav .menu-link--l2{ padding-left:1rem; padding-right:1rem; }
        .hdr-row2 .header-nav .menu-link--l3{ padding-left:1.5rem; padding-right:1.5rem; }
      }

      /* Список языков перекрывал пункты меню — теперь он и меню друг друга
         закрывают (см. разметку), но на всякий случай поднимаем его выше. */
      .hdr-lang-menu{ z-index:1001; }

      /* ⚠️ И раскрывается он ВПРАВО от кнопки, а не влево.
         Базовое правило прибивает список правым краем к кнопке (`right:0`).
         На широком экране кнопка стоит справа, и список уходит внутрь окна.
         На телефоне она первая в ряду действий, то есть почти у левого
         края, — и список шириной 11.5rem уезжал за левую границу экрана. */
      .hdr-lang-menu{ left:0; right:auto; }
    }

    /* Совсем узкие (360 и меньше): ужимаем отступы, а не размеры нажатия. */
    @media (max-width: 380px){
      .hdr-row1{ padding-left:.6rem; padding-right:.6rem; }
      .hdr-actions .hdr-pill,
      .hdr-actions .hdr-icon-btn{ min-width:40px; padding:0 .2rem; }
      .hdr-logo-name{ font-size:1rem; }
    }
  </style>
</header>
