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
