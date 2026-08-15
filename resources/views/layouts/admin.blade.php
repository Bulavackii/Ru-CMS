<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Панель управления')</title>

  {{-- favicon.png в проекте нет — иконка лежит в SVG, как и на фронтенде --}}
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
  @include('layouts.partials.tw-compat')
  <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}" crossorigin="anonymous" referrerpolicy="no-referrer"/>
  @php
    // ==== ТЕМА В АДМИНКЕ ====
    // $activeTheme раздаёт ThemeServiceProvider через View::composer('*').
    // Раньше админка цвета темы не использовала вовсе: акцент был прибит
    // литералами, и выбранная тема на панель не влияла.
    //
    // Поверх активной темы сайта учитывается ЛИЧНЫЙ выбор администратора
    // (переключатель оформления в шапке → session('admin_theme')). Выбор не
    // меняет оформление сайта: панель просто перекрашивает свой акцент.
    // $panelTheme приходит из ThemeServiceProvider; здесь ?? на случай,
    // если вьюху рендерят в обход композера.
    $panelTheme   = $panelTheme ?? $activeTheme ?? null;
    $adminTokens  = $panelTheme->tokens ?? [];
    $adminPrimary = data_get($adminTokens, 'colors.primary', '#6366f1');
    $adminAccent  = data_get($adminTokens, 'colors.accent',  '#a855f7');
  @endphp

  <style>
    [x-cloak]{display:none!important}

    /* Акцент панели = акцент активной темы. Фон и текст админки намеренно
       НЕ берём из темы: она задаёт оформление сайта, а панель должна
       оставаться читаемой при любой (в том числе тёмной) теме сайта. */
    :root{
      --admin-primary: {{ $adminPrimary }};
      --admin-accent: {{ $adminAccent }};
      --admin-primary-soft: rgba(99,102,241,.12);
      --admin-primary-soft: color-mix(in srgb, var(--admin-primary) 12%, transparent);
      --admin-primary-glow: rgba(79,70,229,.28);
      --admin-primary-glow: color-mix(in srgb, var(--admin-primary) 28%, transparent);
      --admin-primary-ink: #312e81;
      --admin-primary-ink: color-mix(in srgb, var(--admin-primary) 70%, #000);
      /* Цвет надписи ПОВЕРХ акцента. Считается по яркости самого акцента:
         акценты тем очень разные, и белым по светло-голубому «Графиту»
         (#38bdf8, контраст 2.14:1) читать было нельзя. См. readable_ink(). */
      --admin-on-primary: {{ readable_ink($adminPrimary) }};
      --admin-on-accent: {{ readable_ink($adminAccent) }};
    }
    /* ── Индиго из разметки → акцент активной темы ──────────────────────
       В шаблонах панели фирменный индиго вписан классами Tailwind: только
       bg-indigo-600 встречается 101 раз в 50 файлах, а вместе с рамками,
       кольцами фокуса и цветом значков — больше шестисот вхождений. Из-за
       этого кнопки вроде «Новый фрагмент» оставались индиго при любой теме,
       хотя соседние кнопки того же экрана уже брали акцент из переменной, и
       на одном экране оказывалось два разных «главных» цвета.

       Правим не в пятидесяти файлах, а здесь: селектор `body.admin-sharp .X`
       весомее одиночного класса Tailwind (0,2,0 против 0,1,0), поэтому
       побеждает без !important и независимо от порядка подключения.

       Область — только панель: класс admin-sharp висит на её теге body,
       сайт и письма правило не задевает. */

    body.admin-sharp .bg-indigo-600,
    body.admin-sharp .bg-indigo-700{ background-color:var(--admin-primary) }
    body.admin-sharp .hover\:bg-indigo-600:hover,
    body.admin-sharp .hover\:bg-indigo-700:hover{ background-color:var(--admin-primary); filter:brightness(1.08) }

    /* Надпись поверх акцента. Белый годится не всегда: у светлых акцентов
       (мятный «Изумруд», голубой «Графит») по нему не прочитать — цвет
       считает readable_ink, см. --admin-on-primary выше.
       Селектор составной: перекрашиваем text-white только там, где он лежит
       НА акценте, а не по всей панели. */
    body.admin-sharp .bg-indigo-600.text-white,
    body.admin-sharp .bg-indigo-700.text-white,
    body.admin-sharp .bg-indigo-600 .text-white{ color:var(--admin-on-primary,#fff) }

    /* Акцентный текст и значки */
    body.admin-sharp .text-indigo-500,
    body.admin-sharp .text-indigo-600,
    body.admin-sharp .text-indigo-700{ color:var(--admin-primary) }
    body.admin-sharp .hover\:text-indigo-600:hover,
    body.admin-sharp .hover\:text-indigo-700:hover{ color:var(--admin-primary) }

    /* Рамки и кольцо фокуса */
    body.admin-sharp .border-indigo-400,
    body.admin-sharp .border-indigo-500,
    body.admin-sharp .border-indigo-600{ border-color:var(--admin-primary) }
    body.admin-sharp .hover\:border-indigo-400:hover,
    body.admin-sharp .hover\:border-indigo-500:hover,
    body.admin-sharp .focus\:border-indigo-500:focus{ border-color:var(--admin-primary) }
    body.admin-sharp .focus\:ring-indigo-500:focus{ --tw-ring-color:var(--admin-primary) }

    /* Мягкая подложка акцента */
    body.admin-sharp .bg-indigo-50,
    body.admin-sharp .bg-indigo-100{ background-color:var(--admin-primary-soft) }

    /* Утилиты Tailwind с /NN-прозрачностью (bg-white/80 и т.п.) отсутствуют в
       собранном public/assets/css/tailwind.min.css — это статическая сборка без
       JIT-сканирования содержимого, в неё вошли только полные стандартные
       классы, а не opacity-модификаторы и произвольные значения. Поэтому
       «стеклянные» полосы шапки/подвала админки задаём литеральным CSS. */
    .admin-glass{background:rgba(255,255,255,.82);backdrop-filter:blur(16px) saturate(160%);-webkit-backdrop-filter:blur(16px) saturate(160%)}
    .dark .admin-glass{background:rgba(17,24,39,.82)}
    /* Тёмный вариант — для единой шапки (header.blade.php), она всегда
       тёмная независимо от выбранного оформления: под неё уже свёрстаны
       components.admin.global-search и notifications-center. */
    .admin-glass-dark{background:rgba(17,24,39,.9);backdrop-filter:blur(16px) saturate(160%);-webkit-backdrop-filter:blur(16px) saturate(160%)}

    /* Дизайн-язык админки: только прямые края, скруглений быть не должно нигде.
       Вместо правки rounded-* по десяткам вьюх и компонентов — один глобальный
       "рубильник", гарантированно перекрывающий и Tailwind-утилиты, и
       литеральный CSS (border-radius в собственном стек-стиле того же дашборда),
       т.к. !important побеждает специфичность независимо от порядка подключения.
       Область действия — только страницы админки (класс на <body> этого лейаута,
       фронтенд и письма его не подключают и не затрагиваются). */
    body.admin-sharp, body.admin-sharp * { border-radius: 0 !important; }

    /* Общие акценты шапки/подвала: полоса-градиент (визуально скрепляет верх
       и низ страницы) и срезанный угол вместо скругления — тот же "прямой,
       но не скучный" приём, что и у кнопки «Создать» в шапке. */
    .admin-accent-bar{height:3px;background:linear-gradient(90deg,var(--admin-primary),var(--admin-accent),var(--admin-primary))}
    .admin-clip-corner{clip-path:polygon(0 0,100% 0,100% 100%,10px 100%,0 calc(100% - 10px))}

    /* Переиспользуемые утилиты админки (та же логика, что и .admin-glass:
       эффекты, которых нет в статической Tailwind-сборке, задаём литеральным
       CSS). Индиго-акцент — общий для дашборда/шапки/сайдбара редизайна.
       Литеральный .dark-селектор ЗДЕСЬ работает (класс .dark реально висит
       на <html>), в отличие от Tailwind dark:-утилит, которых в сборке нет. */
    .admin-icon-badge{display:inline-flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;background:linear-gradient(135deg,var(--admin-primary),var(--admin-accent));color:#fff;flex:none}
    .admin-card{background:var(--surface,#fff);border:1px solid var(--surface-bd,#e5e7eb);transition:box-shadow .2s ease,transform .2s ease}
    .admin-card:hover{box-shadow:0 12px 24px -10px var(--admin-primary-glow);transform:translateY(-2px)}
    .dark .admin-card{background:#1f2937;border-color:#374151}
    /* Пояснение под полем — обычный мелкий серый текст.
       Раньше здесь была заливка с полосой слева, и короткая подсказка вроде
       «0 — висит, пока не закроют» читалась как выделенный маркером текст:
       на форме с десятком полей глаз цеплялся за подсказки, а не за поля.
       Тот же вывод уже был сделан в Геолокации (.geo-help) — теперь правило
       общее для всей панели. */
    .admin-hint{font-size:.8rem;line-height:1.5;color:var(--surface-mute,#64748b)}
    .dark .admin-hint{color:#94a3b8}

    /* Врезка-примечание — то, чем .admin-hint был раньше. Оставлена для
       случаев, где текст ДОЛЖЕН выделяться: предупреждение над формой,
       пояснение к разделу целиком. Отличается наличием отступов в месте
       применения — короткие подсказки под полями их не имеют. */
    .admin-note{border-left:3px solid var(--admin-primary);background:var(--admin-primary-soft);color:var(--admin-primary-ink)}
    .dark .admin-note{background:var(--admin-primary-soft);color:#c7d2fe}

    /* Рабочий тумблер. Tailwind-вариант peer-checked: в статической сборке
       ОТСУТСТВУЕТ (как dark:/opacity/arbitrary), из-за чего все тумблеры на
       peer-checked не переключались визуально. Здесь состояние берётся
       настоящим CSS-селектором input:checked ~ .track/.knob — он работает
       независимо от сборки Tailwind. */
    /* Высота закреплённой шапки панели. Её знает не только сама шапка:
       на неё опирается всё, что тоже прилипает к верху — иначе элементы
       съезжаются в одну точку и перекрывают друг друга. Панель инструментов
       редактора читает эту переменную (см. ru-editor.css). */
    :root{ --admin-header-h: 60px; --ru-ed-stick-top: 60px }

    .admin-toggle{position:relative;display:inline-block;width:2.5rem;height:1.4rem;flex:none}
    .admin-toggle input{position:absolute;inset:0;width:100%;height:100%;opacity:0;margin:0;cursor:pointer;z-index:2}
    .admin-toggle .track{position:absolute;inset:0;background:#cbd5e1;transition:background .2s}
    .admin-toggle .knob{position:absolute;top:2px;left:2px;width:calc(1.4rem - 4px);height:calc(1.4rem - 4px);background:var(--surface,#fff);transition:left .2s;box-shadow:0 1px 2px rgba(0,0,0,.25);pointer-events:none}
    .admin-toggle input:checked ~ .track{background:var(--admin-primary)}
    .admin-toggle input:checked ~ .knob{left:calc(100% - 1.4rem + 2px)}
    .dark .admin-toggle .track{background:#4b5563}

    /* ═════════ Телефоны и планшеты ═════════
       Порог тот же, что на сайте: 1024 включительно — iPad в альбомной
       ориентации ровно такой, и он сенсорный.

       Панель рисовалась под мышь: кнопки шапки 28×26, отметки строк 13×13,
       подписи по 9,6–11 пикселей. Пальцем в такое не попасть, а мельче
       двенадцати браузер на телефоне предлагает увеличить страницу целиком.

       ⚠️ Селекторы начинаются с body намеренно. Стили шапки, подвала,
       дашборда и вьюх модулей лежат в своих файлах, то есть НИЖЕ по
       документу, и при равной силе побеждают они: без этого префикса
       правила просто не срабатывали (замер показывал прежние 28×26). */
    @media (max-width: 1024px), (max-height: 500px){
        /* ── Шапка ──────────────────────────────────────────── */
        body .ahd-btn{ width:40px; height:40px }
        body .ahd-group .ahd-btn{ width:40px; height:40px }
        body .ahd-action{ min-height:40px }
        /* Колокольчик уведомлений и меню профиля класса .ahd-btn не носят —
           они собраны утилитами и остались 28×26. */
        body .ahd-group > button,
        body .ahd-group > div > button{ min-width:40px; min-height:40px }

        /* ── Отметки строк в списках ───────────────────────────
           13×13 в каждой таблице панели. 24 — нижняя граница WCAG 2.5.8. */
        body input[type=checkbox], body input[type=radio]{
            width:24px; height:24px; flex:none }
        /* Тумблер .admin-toggle держит НЕВИДИМЫЙ input во всю плашку —
           размер ему задавать нельзя, иначе нажимается мимо. */
        body .admin-toggle input{ width:100%; height:100% }

        /* ── Кнопки-пилюли, вкладки и отборы ──────────────────
           Собраны утилитами (px-3 py-1.5), высота выходит 26–30. */
        body .dash-pill,
        body .pos-filter,
        body .file-action{ min-height:36px }
        body .adm-f-social{ width:36px; height:36px }

        /* ── Действия на плитках медиатеки ─────────────────────
           Они показывались только при наведении (opacity:0 → :hover).
           На сенсорном экране наведения НЕТ вовсе — скачать и удалить
           файл с телефона было физически нечем. */
        body .file-actions{ opacity:1 }

        /* Ссылки-пилюли (вкладки отбора, действия в карточках) собраны
           утилитами inline-flex + px-3 py-1.5 и выходят по 26–28. */
        body a.inline-flex, body button.inline-flex{ min-height:36px }
        body .file-action{ min-width:36px }
        /* Ссылки на документацию у способов оплаты и доставки, чипы SEO и
           кнопки локализации: 18–25 по высоте. */
        body .pm-docs, body .dl-docs, body .seo-chip,
        body .loc-btn{ min-height:24px }

        /* Подсказка про Ctrl+K в поиске: на сенсорном экране сочетания
           клавиш нет, а подпись самая мелкая на странице. */
        body .ags-kbd{ display:none }

        /* Ссылки в таблицах разделов — заголовки материалов, страниц и
           пользователей. Голая строка текста высотой 17 пикселей: промах
           пальцем открывает соседнюю запись. */
        body table a{ display:inline-block; min-width:24px; min-height:24px;
                      padding-block:.15rem; text-align:center }

        /* ── Нижняя граница кегля ───────────────────────────────
           Мелкий текст панели — это системно подписи, чипы, состояния и
           технические коды, и называются они одинаково во всех разделах.
           Перечислять их поштучно бессмысленно: следующая вьюха заведёт
           такой же класс и снова окажется нечитаемой. Отбор по куску имени
           покрывает и уже написанное, и будущее; свойство здесь одно —
           кегль, поэтому лишнее совпадение безобидно. */
        body [class*="label"], body [class*="chip"], body [class*="state"],
        body [class*="code"], body [class*="count"], body [class*="note"],
        body [class*="__who"], body [class*="__when"], body [class*="__where"],
        body [class*="hint"], body [class*="crumb"],
        body .adm-f-chip > b, body .ahd-user-ava, body .adm-f-key,
        body .adm-f-ver, body .adm-f-soc-label, body .dash-sec__flag,
        body .ord-h2, body .loc-h2,
        body .theme-swatch, body .mod-name__meta, body .mod-name__meta *,
        body [class*="slug"], body [class*="__cap"], body [class*="eyebrow"],
        /* Метка языка в шапке объявлена как .ahd-group .ahd-btn--wide span —
           селектор из двух классов, поэтому обойтись одним body мало. */
        body .ahd-user-role, body .ahd-group .ahd-btn--wide span,
        body .asb-group{ font-size:12px }

        /* Тумблер: плашка 40×22, то есть по высоте ниже нижней границы.
           Растёт целиком — невидимый input тянется за ней (inset:0). */
        body .admin-toggle{ width:2.9rem; height:1.65rem }
        body .admin-toggle .knob{ width:calc(1.65rem - 4px); height:calc(1.65rem - 4px) }
        body .admin-toggle input:checked ~ .knob{ left:calc(100% - 1.65rem + 2px) }

        /* Исключение: карточка темы — это МАКЕТ сайта в миниатюре, а не
           подписи панели. Поднимать в нём кегль нельзя, иначе внутри
           уменьшенной страницы текст перестанет быть уменьшенным. */
        body .theme-preview__text, body .theme-preview__btn{ font-size:inherit }

        /* ── Поля форм ─────────────────────────────────────────
           16 пикселей — иначе Safari на iOS увеличивает страницу при
           фокусе, и панель уезжает за край. */
        body input[type=text], body input[type=email], body input[type=password],
        body input[type=number], body input[type=search], body input[type=url],
        body input[type=date], body select, body textarea{
            min-height:44px; font-size:16px }
    }
  </style>

  {{-- Vite для основного JS (Alpine и другие) --}}
  @vite(['resources/js/app.js'])

  {{-- Стек стилей для страниц с собственным точечным CSS (например, дашборда) --}}
  @stack('styles')

  {{-- Раньше здесь включался класс .dark по флагу localStorage('darkMode'),
       который ставила кнопка-луна в шапке. Кнопка убрана: в собранном
       tailwind.min.css нет ни одного dark:-варианта, поэтому класс красил
       только те несколько блоков, для которых ниже написаны литеральные
       .dark-правила, — панель выглядела наполовину перекрашенной. Оформление
       панели теперь меняется темой (переключатель в шапке).
       Флаг подчищаем, иначе тот, кто когда-то включил «тёмную тему», остался
       бы в ней навсегда: выключать её стало нечем. --}}
  <script>
    (function() {
      try { localStorage.removeItem('darkMode'); } catch (e) {}
    })();
  </script>
</head>

<body class="admin-sharp bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors duration-200 min-h-screen">
  {{-- Мобильное меню --}}
  @include('layouts.admin.mobile-menu')

  {{-- фиксированный сайдбар --}}
  @include('layouts.admin.sidebar')

  {{-- каркас с липким верхом и обычным футером --}}
  <div id="admin-wrap" class="min-h-screen flex flex-col lg:pl-60 transition-all duration-300">

    {{-- ⬇️ Новый общий липкий контейнер для header + navbar --}}
    <div class="sticky top-0 z-50">
      @include('layouts.admin.header')
    </div>
    {{-- ⬆️ Конец липкого контейнера --}}

    <main class="flex-1 p-4 sm:p-6 md:p-8 lg:p-10 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
      @include('layouts.partials.flash')
      @yield('content')
    </main>

    @include('layouts.admin.footer')
  </div>

  {{-- Alpine приходит ОДИН раз — из сборки (resources/js/app.js, подключается
       через @vite выше: там же import Alpine и Alpine.start()). Отдельный
       alpine.min.js отсюда убран: он поднимал ВТОРОЙ экземпляр Alpine, и тот
       инициализировал разметку повторно — каждый x-for в панели рендерился
       дважды (в подсказках поиска вместо 4 пунктов было 8, то же самое во
       всех списках админки на x-for). Поймано вживую 26.07.2026.
       В layouts/frontend-install.blade.php отдельный alpine.min.js оставлен
       намеренно: там @vite нет, мастеру установки Alpine взять больше неоткуда. --}}
  <script src="{{ asset('js/admin/notifications.js') }}"></script>
  {{-- Копирование в буфер.
       Кнопки в Формах и Каптче звали window.toast, которого в проекте нет
       вовсе: текст копировался, но человек не видел НИЧЕГО и справедливо
       считал, что кнопка не работает. Подтверждение показывает сама кнопка —
       для этого не нужен ни общий всплывающий уведомитель, ни его настройка.

       Запасной путь через временное поле нужен для страниц, открытых не по
       localhost и не по https: там Clipboard API недоступен. --}}
  <script>
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-copy]');

      if (!button) {
        return;
      }

      var text = button.getAttribute('data-copy');

      var done = function () {
        if (button.dataset.copyBusy) {
          return;
        }

        button.dataset.copyBusy = '1';
        var before = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> ' + (button.getAttribute('data-copied') || 'OK');

        window.setTimeout(function () {
          button.innerHTML = before;
          delete button.dataset.copyBusy;
        }, 1400);
      };

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done).catch(function () {});
        return;
      }

      var area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.left = '-9999px';
      document.body.appendChild(area);
      area.select();

      try {
        document.execCommand('copy');
        done();
      } catch (error) {
        /* Копирование запрещено настройками браузера — молчим. */
      }

      area.remove();
    });
  </script>

  @stack('scripts')

  {{-- Обработка ошибок загрузки ресурсов --}}
  <script>
    (function() {
      // Проверка загрузки Alpine.js
      window.addEventListener('load', function() {
        if (typeof Alpine === 'undefined') {
          console.warn('Alpine.js не загружен. Проверьте путь к файлу.');
        }
      });

      // Обработка ошибок загрузки скриптов
      document.addEventListener('error', function(e) {
        if (e.target.tagName === 'SCRIPT') {
          console.error('Ошибка загрузки скрипта:', e.target.src);
        }
      }, true);

      // Обработка ошибок загрузки стилей
      document.addEventListener('error', function(e) {
        if (e.target.tagName === 'LINK' && e.target.rel === 'stylesheet') {
          console.error('Ошибка загрузки стилей:', e.target.href);
        }
      }, true);
    })();
  </script>

  <script>
    (function () {
      const sb = document.querySelector('aside');
      const wrap = document.getElementById('admin-wrap');
      function apply() {
        if (!sb || !wrap) return;
        const w = Math.round(sb.getBoundingClientRect().width);
        wrap.style.paddingLeft = w ? (w + 'px') : '';
      }
      if (window.ResizeObserver && sb) new ResizeObserver(apply).observe(sb);
      window.addEventListener('resize', apply, { passive: true });
      document.addEventListener('DOMContentLoaded', apply);
    })();
  </script>

  {{-- Финальный проход Lucide после полной загрузки DOM — подхватывает иконки из @yield('content') и всех компонентов --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons(); } catch (e) {}
      }
    });
  </script>
</body>
</html>
